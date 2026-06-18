<?php

namespace App\Livewire\MyAccount;

use App\Models\Media;
use App\Models\StorageType;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketType;
use App\Models\User;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use App\Services\MediaService;
use SplFileInfo;

class Support extends Component
{
    protected $listeners = ['refreshComponent'];

    public $perPage = 8;

    public function render()
    {
        $user = Auth::user();

        return view('livewire.' . config('constants.theme') . '.my-account.support', [
            'user_info' => $user,
            'ticket_types' => fetchDetails(TicketType::class),
            'tickets' => Ticket::where('user_id', $user->id)->orderBy('id', 'desc')->paginate($this->perPage),
        ]);
    }


    public function get_tickets($user_id)
    {

        $user_tickets = fetchDetails(Ticket::class, ['user_id' => $user_id], "*", "", "", "tickets.id", "DESC");
        $totle_tickets = count($user_tickets);
        $tickets = collect($user_tickets);
        $page = request()->get('page', 1);
        if (isset($page)) {
            $perPage = 8;
            $paginator = new LengthAwarePaginator(
                $tickets->forPage((int) $page, (int) $perPage),
                $totle_tickets,
                (int) $perPage,
                (int) $page,
                ['path' => url()->current()]
            );
        }
        $tickets['tickets'] = $paginator->items();
        $tickets['links'] = $paginator->links();
        return $tickets;
    }

    public function get_ticket_by_id(Request $request)
    {
        if (!empty($request['user_id']) && !empty($request['ticket_id'])) {
            $user_ticket = fetchDetails(Ticket::class, ['user_id' => $request['user_id'], 'id' => $request['ticket_id']]);
            $response['error'] = false;
            $response['data'] = $user_ticket[0];
            return $response;
        }
    }

    public function refreshComponent()
    {
        $this->dispatch('$refresh');
    }

    public function add_ticket(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'ticket_type' => 'required',
                'ticket_email' => 'required|email',
                'ticket_subject' => 'required',
                'ticket_description' => 'required',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors();
            $response['error'] = true;
            $response['message'] = $errors;
            return $response;
        }
        $user_id = Auth::user()->id;
        $ticket_data = [
            'user_id' => $user_id,
            'ticket_type_id' => $request['ticket_type'],
            'subject' => $request['ticket_subject'],
            'email' => $request['ticket_email'],
            'description' => $request['ticket_description'],
            'status' => config('constants.PENDING'),
        ];
        if ($request['ticket_id'] != null) {
            $ticket = Ticket::find($request['ticket_id']);
            $res = $ticket->update($ticket_data);
        } else {
            $res = Ticket::Create($ticket_data);
        }
        if (!$res) {
            $response['error'] = true;
            $response['message'] = 'Something Went Wrong Please Try Again Later!';
            return $response;
        }
        if ($request['ticket_id'] != null) {
            $response['error'] = false;
            $response['message'] = 'Ticket Updated SuccessFully.';
            return $response;
        }
        $response['error'] = false;
        $response['message'] = 'Ticket Added SuccessFully.';
        return $response;
    }

    public function getMessages($ticket_id = "", $user_id = "", $search = "", $offset = 0, $limit = 10, $sort = "id", $order = "DESC", $data = [], $msg_id = "")
    {
        $ticket_id = request()->input('ticket_id', '');
        // $user_id = Auth::id();
        $search = trim(request()->input('search', ''));
        $offset = request()->input('offset', 0);
        $limit = request()->input('limit', 10);

        $data = config('eshop_pro.type');
        $multipleWhere = [];
        $where = [];
        if (!empty($search)) {
            $multipleWhere = [
                'u.id' => $search,
                'u.username' => $search,
                't.subject' => $search,
                'tm.message' => $search,
            ];
        }
        if (!empty($ticket_id)) {
            $where['tm.ticket_id'] = $ticket_id;
        }
        if (!empty($user_id)) {
            $where['tm.user_id'] = $user_id;
        }
        if (!empty($msg_id)) {
            $where['tm.id'] = $msg_id;
        }
        $countRes = DB::table('ticket_messages as tm')
            ->leftJoin('tickets as t', 't.id', '=', 'tm.ticket_id')
            ->leftJoin('users as u', 'u.id', '=', 'tm.user_id')
            ->select(DB::raw('COUNT(tm.id) as total'));
        if (!empty($multipleWhere)) {
            $countRes->where(function ($query) use ($multipleWhere) {
                foreach ($multipleWhere as $column => $value) {
                    $query->orWhere($column, 'like', '%' . $value . '%');
                }
            });
        }
        if (!empty($where)) {
            $countRes->where($where);
        }
        $total = $countRes->first()->total;
        $searchRes = DB::table('ticket_messages as tm')
            ->leftJoin('tickets as t', 't.id', '=', 'tm.ticket_id')
            ->leftJoin('users as u', 'u.id', '=', 'tm.user_id')
            ->select(
                'tm.id',
                'tm.user_type as user_type',
                'tm.user_id',
                'tm.ticket_id',
                'tm.message',
                'u.username as name',
                'tm.attachments',
                't.subject',
                'tm.updated_at',
                'tm.created_at'
            );
        if (!empty($multipleWhere)) {
            $searchRes->where(function ($query) use ($multipleWhere) {
                foreach ($multipleWhere as $column => $value) {
                    $query->orWhere($column, 'like', '%' . $value . '%');
                }
            });
        }
        if (!empty($where)) {
            $searchRes->where($where);
        }
        $msgSearchRes = $searchRes->orderBy($sort, $order)->skip($offset)->take($limit)->get();
        $rows = [];
        $bulkData = [
            'error' => $msgSearchRes->isEmpty(),
            'message' => $msgSearchRes->isEmpty() ? labels('admin_labels.ticket_messages_not_exist', 'Ticket Message(s) does not exist')
                :
                labels('admin_labels.messages_retrieved_successfully', 'Message retrieved successfully'),
            'total' => $total,
            'data' => [],
        ];
        if (!$msgSearchRes->isEmpty()) {
            foreach ($msgSearchRes as $row) {
                $row = (array) $row;
                $tempRow = [
                    'id' => $row['id'],
                    'user_type' => $row['user_type'],
                    'user_id' => $row['user_id'],
                    'ticket_id' => $row['ticket_id'],
                    'message' => !empty($row['message']) ? $row['message'] : "",
                    'name' => $row['name'],
                    'attachments' => [],
                    'subject' => $row['subject'],
                    'updated_at' => $row['updated_at'],
                    'created_at' => Carbon::parse($row['created_at'])->format('d-m-Y'),
                ];
                if (!empty($row['attachments']) && $row['attachments'] != '' && $row['attachments'] != "null") {
                    $attachments = json_decode($row['attachments'], true);
                    $counter = 0;
                    foreach ($attachments as $row1) {
                        $tmpRow = [
                            'media' => app(MediaService::class)->getMediaImageUrl($row1),
                        ];
                        $file = new SplFileInfo($row1);
                        $ext = $file->getExtension();
                        if (in_array($ext, $data['image']['types'])) {
                            $tmpRow['type'] = "image";
                        } elseif (in_array($ext, $data['video']['types'])) {
                            $tmpRow['type'] = "video";
                        } elseif (in_array($ext, $data['document']['types'])) {
                            $tmpRow['type'] = "document";
                        } elseif (in_array($ext, $data['archive']['types'])) {
                            $tmpRow['type'] = "archive";
                        }
                        $attachments[$counter] = $tmpRow;
                        $counter++;
                    }
                } else {
                    $attachments = [];
                }
                $tempRow['attachments'] = $attachments;
                $rows[] = $tempRow;
            }
            $bulkData['data'] = $rows;
        }
        return $bulkData;
    }

    public function sendMessage(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'ticket_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            if ($request->ajax()) {
                return response()->json(['errors' => $errors->all()], 422);
            }
            return redirect()->back()->withErrors($errors)->withInput();
        }
        $user_id = auth()->id();
        $ticket_id = $request->input('ticket_id');
        $message = $request->input('message');
        $user = User::find($user_id);
        if (!$user) {
            return response()->json([
                'error' => true,
                'message' =>
                labels('admin_labels.user_not_found', 'User not found!'),
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_token(),
                'data' => []
            ]);
        }

        // Check if ticket is awaiting admin reply (customers cannot chat until admin replies)
        $ticket = Ticket::find($ticket_id);
        if ($ticket && $ticket->awaiting_admin_reply) {
            return response()->json([
                'error' => true,
                'message' => labels('front_messages.awaiting_admin_reply', 'Chat will be available after admin responds to your ticket.'),
                'data' => []
            ]);
        }


        $uploaded_images = [];


        if (!File::exists('storage/tickets')) {
            File::makeDirectory('storage/tickets', 0755, true);
        }

        //code for upload media attachements

        try {
            $media_storage_settings = fetchDetails(StorageType::class, ['is_default' => 1], '*');
            $mediaStorageType = !$media_storage_settings->isEmpty() ? $media_storage_settings[0]->id : 1;
            $disk = !$media_storage_settings->isEmpty() ? $media_storage_settings[0]->name : 'public';

            $media = StorageType::find($mediaStorageType);

            $mediaIds = [];

            if ($request->hasFile('attachments')) {

                $files = $request->file('attachments');

                foreach ($files as $file) {
                    $mediaItem = $media->addMedia($file)
                        ->sanitizingFileName(function ($fileName) use ($media) {
                            // Replace special characters and spaces with hyphens
                            $sanitizedFileName = strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));

                            // Generate a unique identifier based on timestamp and random component
                            $uniqueId = time() . '_' . mt_rand(1000, 9999);

                            $extension = pathinfo($sanitizedFileName, PATHINFO_EXTENSION);
                            $baseName = pathinfo($sanitizedFileName, PATHINFO_FILENAME);

                            return "{$baseName}-{$uniqueId}.{$extension}";
                        })
                        ->toMediaCollection('tickets', $disk);

                    $mediaIds[] = $mediaItem->id;

                    if ($disk == 'public') {
                        $uploaded_images[] = 'tickets/' . $mediaItem->file_name;
                    }
                }
            }
            if ($disk == 's3') {
                $media_list = $media->getMedia('tickets');
                for ($i = 0; $i < count($mediaIds); $i++) {
                    $media_url = $media_list[($media_list->count()) - (count($mediaIds) - $i)]->getUrl();

                    $uploaded_images[] = $media_url;

                    Media::destroy($mediaIds[$i]);
                }
            }
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        }

        $ticket_messages = new TicketMessage([
            'user_type' => 'user',
            'user_id' => $user_id,
            'ticket_id' => $ticket_id,
            'message' => $message,
            'attachments' => json_encode($uploaded_images) ?? [],
            'disk' => $disk ?? '',
        ]);
        $response = $ticket_messages->save();
        $last_insert_id = $ticket_messages->id;

        if ($response) {
            $type = config('eshop_pro.type');
            $result = $this->getMessages($ticket_id, $user_id, "", "", "1", "id", "DESC", $type, $last_insert_id);
            return response()->json([
                'error' => false,
                'message' =>
                labels('admin_labels.ticket_message_sent_successfully', 'Ticket message sent successfully'),
                'data' => $result['data'][0]
            ]);
        } else {
            return response()->json([
                'error' => true,
                'message' =>
                labels('admin_labels.ticket_message_not_sent', 'Ticket message could not be sent!'),
                'data' => []
            ]);
        }
    }
}
