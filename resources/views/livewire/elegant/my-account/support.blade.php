@php
    $bread_crumb['page_main_bread_crumb'] = labels('front_messages.support', 'Support');
@endphp

<div id="page-content">
    <x-utility.breadcrumbs.breadcrumbTwo :$bread_crumb />
    <div class="container-fluid">
        <div class="row">
            <x-utility.my_account_slider.account_slider :$user_info />
            <div class="col-12 col-sm-12 col-md-12 col-lg-9">
                <div class="dashboard-content h-100">
                    <div class="h-100" id="profile">
                        <div class="top-sec d-flex-justify-center justify-content-between mb-4">
                            <div class="d-flex-center">
                                <h2 class="mb-0">{{ labels('front_messages.tickets', 'Tickets') }}</h2>
                                <p class="fs-6 m-0 ms-3">
                                    {{ labels('front_messages.total', 'Total') }}:<b>{{ $tickets->total() }}</b>
                                </p>
                            </div>
                            <button wire:ignore type="button" class="btn btn-primary btn-sm AddNewTicket"
                                data-bs-toggle="modal" data-bs-target="#AddNewTicket"><ion-icon name="add-outline"
                                    class="me-1 fs-5"></ion-icon>
                                {{ labels('front_messages.add', 'Add New Ticket') }}</button>
                        </div>
                        <div class="profile-book-section mb-4">
                            <table>
                                <thead>
                                    <th>{{ labels('front_messages.no', 'No') }}.</th>
                                    <th>#id</th>
                                    <th>{{ labels('front_messages.title', 'Title') }}</th>
                                    <th>{{ labels('front_messages.status', 'Status') }}</th>
                                    <th>{{ labels('front_messages.created_date', 'Created Date') }}</th>
                                    <th>{{ labels('front_messages.updated_date', 'Updated Date') }}</th>
                                    <th>{{ labels('front_messages.action', 'Action') }}</th>
                                </thead>
                                <tbody class="ticket_tbody">
                                    @foreach ($tickets as $key => $ticket)

                                        @php
                                            $type = '';
                                            foreach ($ticket_types as $ticket_type) {
                                                if ($ticket_type->id == $ticket->ticket_type_id) {
                                                    $type = $ticket_type->title;
                                                    break;
                                                }
                                            }
                                        @endphp
                                        <tr class="ticket_card">
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $ticket->id }}</td>
                                            <td class="fs-6 fw-500">{{ $ticket->subject }}</td>
                                            <td class="d-flex-center">
                                                @if ($ticket->status == 1)
                                                    <div class="circle-status in-review-status"></div>
                                                    <p class="ticket_status">
                                                        {{ labels('front_messages.in_review', 'In Review') }}
                                                    </p>
                                                @elseif ($ticket->status == 2)
                                                    <div class="circle-status open-status"></div>
                                                    <p class="ticket_status">
                                                        {{ labels('front_messages.opened', 'Opened') }}
                                                    </p>
                                                @elseif ($ticket->status == 3)
                                                    <div class="circle-status resolved-status"></div>
                                                    <p class="ticket_status">
                                                        {{ labels('front_messages.resolved', 'Resolved') }}
                                                    </p>
                                                @elseif ($ticket->status == 4)
                                                    <div class="circle-status close-status"></div>
                                                    <p class="ticket_status">
                                                        {{ labels('front_messages.closed', 'Closed') }}
                                                    </p>
                                                @elseif ($ticket->status == 5)
                                                    <div class="circle-status reopen-status"></div>
                                                    <p class="ticket_status">
                                                        {{ labels('front_messages.reopened', 'Reopened') }}
                                                    </p>
                                                @endif
                                            </td>
                                            <td>{{ $ticket->created_at }}</td>
                                            <td>{{ $ticket->updated_at }}</td>
                                            <td>
                                                <ion-icon wire:ignore class="fs-5 AddNewTicket cursor-pointer"
                                                    data-ticket-id='{{ $ticket->id }}' name="pencil-sharp"
                                                    data-bs-toggle="modal" data-bs-target="#AddNewTicket"></ion-icon>

                                                <ion-icon
                                                    class="fs-5 cursor-pointer chat-icon view_ticket {{ $ticket->awaiting_admin_reply ? 'disabled_chat_btn' : '' }}"
                                                    data-ticket-id='{{ $ticket->id }}' name="chatbubbles-sharp"
                                                    data-date_created='{{ $ticket->created_at }}'
                                                    data-subject='{{ $ticket->subject }}'
                                                    data-status='{{ $ticket->status }}'
                                                    data-awaiting-admin-reply='{{ $ticket->awaiting_admin_reply ? 1 : 0 }}'
                                                    data-ticket_type='{{ $type }}'
                                                    @if(!$ticket->awaiting_admin_reply)
                                                        data-bs-toggle='modal' data-bs-target='#customer_ticket_chat_modal'
                                                    @endif
                                                    data-user-id='{{ $user_info->id }}'
                                                    @if($ticket->awaiting_admin_reply)
                                                        title="{{ labels('front_messages.awaiting_admin_reply', 'Chat will be available after admin responds') }}"
                                                    @endif>
                                                </ion-icon>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <!--End Product Grid-->
                            <div class="d-flex justify-content-between align-content-center">
                                {{ $tickets->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div wire:ignore.self class="modal fade" id="AddNewTicket" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5 add_new_ticket" id="exampleModalLabel">
                        {{ labels('front_messages.add_new_ticket', 'Add New Ticket') }}
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row gap-2">
                        <label for="ticket_type"
                            class="spr-form-label">{{ labels('front_messages.ticket_type', 'Ticket Type') }}

                            <select name="ticket_type" id="ticket_type">
                                <option value="">{{ labels('front_messages.select_ticket', 'Select Ticket') }}
                                </option>
                                @foreach ($ticket_types as $ticket_type)
                                    <option value="{{ $ticket_type->id }}" title="{{ $ticket_type->title }}">
                                        {{ \Illuminate\Support\Str::limit($ticket_type->title, 80) }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label for="ticket_email" class="spr-form-label">{{ labels('front_messages.email', 'Email') }}
                            <input type="email" name="ticket_email" id="ticket_email" placeholder="{{ labels('front_messages.write_your_email', 'Write Your Email') }}">
                        </label>
                        <label for="ticket_subject"
                            class="spr-form-label">{{ labels('front_messages.subject', 'Subject') }}
                            <input type="text" name="ticket_subject" id="ticket_subject" placeholder="{{ labels('front_messages.subject', 'Subject') }}">
                        </label>
                        <label for="ticket_description"
                            class="spr-form-label">{{ labels('front_messages.description', 'Description') }}
                            <textarea type="text" name="ticket_description" id="ticket_description"
                                placeholder="{{ labels('front_messages.description', 'Description') }}"></textarea>
                        </label>
                        <input type="hidden" name="ticket_id" id="ticket_id">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">{{ labels('front_messages.close', 'Close') }}</button>
                    <button type="submit"
                        class="btn btn-primary add_ticket_btn">{{ labels('front_messages.add', 'Add') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="customer_ticket_chat_modal" tabindex="-1" role="dialog"
        aria-labelledby="exampleModalCenterTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg border-0 rounded-lg">
                <!-- Enhanced Modal Header -->
                <div class="modal-header">
                    <h1 class="modal-title fs-5 add_new_ticket" id="exampleModalLabel">
                        {{ $user_info->username }}
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form class="form-horizontal" id="ticket_send_msg_form"
                    action="{{ route('my-account.support.send-message') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="user_id" id="user_id" value="{{$user_info->id}}">
                    <input type="hidden" name="user_type" id="user_type" value="user">
                    <input type="hidden" name="ticket_id" id="ticket_id">
                    <div class="modal-body p-0">
                        <!-- Ticket Info Header -->
                        <div class="align-items-center card-header d-flex justify-content-between">
                            <div>
                                <h4 class="card-title" id="ticket_type_chat"></h4>
                                <h3 class="subject_chat" id="subject_chat"></h3>
                            </div>
                            <div>
                                <span id="status"><label class="badge badge-light-secondary ml-2"></label></span>
                                <p id="date_created"></p>
                            </div>
                        </div>
                        @php
                            $offset = 0;
                            $limit = 15;
                        @endphp
                        <!-- Chat Messages Area -->
                        <div class="chat-container">
                            <div class="direct-chat-messages p-4" id="element">
                                <div class="ticket_msg" data-limit="<?= $limit ?>" data-offset="<?= $offset ?>"
                                    data-max-loaded="false">
                                    <!-- Messages will be loaded here -->
                                </div>
                                <div class="scroll_div"></div>
                            </div>
                        </div>
                        <!-- Message Input Area -->
                        <div class="message-input-area border-top bg-white p-4">
                            <div class="row g-3">
                                <!-- Message Input -->
                                <div class="card-body d-none" id="chat-dropbox">
                                    <div class="dropzone" id="myAlbumTicket"></div>
                                    <div class="text-center mt-3">
                                        <button class="btn btn-danger shadow-none" onclick="closeDropZone();">Close
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-group">
                                        <button type="button" class="btn btn-primary btn-md px-4 btn-attech">
                                            <ion-icon name="add-outline" class="me-1 fs-5"></ion-icon>
                                        </button>
                                        <input type="text" class="form-control form-control-lg border-0 bg-light"
                                            name="message" id="message_input" placeholder="{{ labels('front_messages.type_your_message_here', 'Type your message here...') }}">
                                        <button type="submit" class="btn btn-primary btn-md px-4" id="submit_btn">
                                            <i class="fas fa-paper-plane me-1"></i>
                                            Send
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
