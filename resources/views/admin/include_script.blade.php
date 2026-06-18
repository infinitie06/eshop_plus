 <!--   Core JS Files   -->
 <script src="{{ asset('/assets/admin/js/jquery.min.js') }}"></script>
 <script src="{{ asset('/assets/admin/js/jquery.js') }}"></script>
 <script src="{{ asset('/assets/js/core/popper.min.js') }}"></script>

 <script src="{{ asset('/assets/js/core/bootstrap.js') }}"></script>

 <script src="{{ asset('/assets/js/plugins/perfect-scrollbar.min.js') }}"></script>
 <script src="{{ asset('/assets/js/plugins/smooth-scrollbar.min.js') }}"></script>
 <script src="{{ asset('/assets/js/plugins/apexcharts.js') }}"></script>
 <script src="{{ asset('assets/admin/js/iziToast.min.js') }}"></script>
 <script src="{{ asset('assets/admin/js/dropzone.js') }}"></script>
 <script src="{{ asset('assets/admin/js/bootstrap-table.min.js') }}"></script>
 <script src="{{ asset('assets/admin/js/select2.min.js') }}"></script>
 <script src="{{ asset('assets/admin/js/tagify.min.js') }}"></script>
 <script src="{{ asset('assets/admin/js/jstree.min.js') }}"></script>
 <script src="{{ asset('assets/admin/js/jquery.blockUI.js') }}"></script>
 <script src="{{ asset('assets/admin/js/jquery-slimScroll.js') }}"></script>
 <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>

 <script src="{{ asset('assets/admin/js/sweetalert2.min.js') }}"></script>
 <script src="{{ asset('assets/admin/js/tinymce.min.js') }}"></script>
 <script src="{{ asset('assets/admin/js/jquery.repeater.min.js') }}"></script>
 <script src="{{ asset('assets/admin/js/jquery.repeater.js') }}"></script>
 <script src="{{ asset('assets/admin/js/sortable.js') }}"></script>
 <script src="{{ asset('assets/admin/js/jquery-sortable.js') }}"></script>
 <script src="{{ asset('assets/admin/js/lightbox.min.js') }}"></script>
 <script src="{{ asset('js/main.js') }}"></script>
 <script>
     var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
     var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
         return new bootstrap.Popover(popoverTriggerEl, {
             trigger: 'hover',
             html: true
         })
     })
 </script>
 <script src="{{ asset('js/sidebarMenu.js') }}"></script>

 <!-- Rating library -->

 <script src="{{ asset('/assets/js/plugins/jquery.rateyo.min.js') }}"></script>

 <!-- =================== js files for datepicker ========================================= -->


 <script src="{{ asset('/assets/admin/js/moment.min.js') }}"></script>
 <script src="{{ asset('/assets/admin/js/daterangepicker.js') }}"></script>

 <!-- =================== js files for stepper ========================================= -->
 <script src="{{ asset('assets/admin/js/jquery.validate.min.js') }}"></script>
 <script src="{{ asset('assets/admin/js/additional-methods.min.js') }}"></script>
 <script src="{{ asset('assets/admin/js/jquery.steps.min.js') }}"></script>

 <script src="{{ asset('assets/admin/js/nouislider.min.js') }}"></script>
 <script src="{{ asset('assets/admin/js/wNumb.js') }}"></script>
 <script src="{{ asset('assets/admin/js/stepper.js') }}?v={{ \Illuminate\Support\Str::random(10) }}"></script>

 <script src="{{ asset('/assets/js/argon-dashboard.min.js') }}"></script>

 {{-- filepond  --}}
 <script src="{{ asset('assets/admin/js/filepond.js') }}"></script>
 <script src="/assets/filepond/dist/filepond.min.js"></script>
 <script src="/assets/filepond/dist/filepond-plugin-image-preview.min.js"></script>
 <script src="/assets/filepond/dist/filepond-plugin-pdf-preview.min.js"></script>
 <script src="/assets/filepond/dist/filepond-plugin-file-validate-size.js"></script>
 <script src="/assets/filepond/dist/filepond-plugin-file-validate-type.js"></script>
 <script src="/assets/filepond/dist/filepond-plugin-image-validate-size.js"></script>
 <script src="/assets/filepond/dist/filepond.jquery.js"></script>


 {{-- bootstrap table export --}}

 <script src="{{ asset('/assets/admin/js/tableExport.min.js') }}"></script>
 <script src="{{ asset('/assets/admin/js/bootstrap-table-export.min.js') }}"></script>
 <script src="{{ asset('/assets/admin/js/moment.min.js') }}"></script>
 <script src="{{ asset('/assets/admin/js/daterangepicker.js') }}"></script>

 {{-- data sortable and dragable js --}}

 <script src="{{ asset('assets/admin/js/TweenMax.min.js') }}"></script>
 <script src="{{ asset('assets/admin/js/draggable.min.js') }}"></script>

 {{-- <script src="{{ asset('assets/admin/custom/custom.js') }}"></script> --}}
 {{-- <script src="https://eshop-pro-dev.eshopweb.store/assets/admin/custom/custom.js?v=152"></script> --}}

 <script src="{{ asset('assets/admin/custom/custom.js') }}?v={{ \Illuminate\Support\Str::random(10) }}"></script>

 <script>
     $(document).ready(function() {
         // Global iziToast settings
         iziToast.settings({
             timeout: 5000,
             resetOnHover: true,
             transitionIn: 'bounceInUp',
             transitionOut: 'fadeOut',
             transitionInMobile: 'fadeInUp',
             transitionOutMobile: 'fadeOutDown',
             position: 'topRight',
             close: true,
             progressBar: true,
             pauseOnHover: true,
         });

         // Session Success
         @if (session('success'))
             iziToast.success({
                 title: "{{ labels('admin_labels.success', 'Success') }}",
                 message: "{{ session('success') }}",
             });
         @endif

         // Session Error
         @if (session('error'))
             iziToast.error({
                 title: "{{ labels('admin_labels.error', 'Error') }}",
                 message: "{{ session('error') }}",
             });
         @endif

         // Session Warning
         @if (session('warning'))
             iziToast.warning({
                 title: "{{ labels('admin_labels.warning', 'Warning') }}",
                 message: "{{ session('warning') }}",
             });
         @endif

         // Session Info
         @if (session('info') || session('message'))
             iziToast.info({
                 title: "{{ labels('admin_labels.info', 'Info') }}",
                 message: "{{ session('info') ?? session('message') }}",
             });
         @endif

         // Session Error Message (Backup key)
         @if (session('error_message'))
             iziToast.error({
                 title: "{{ labels('admin_labels.error', 'Error') }}",
                 message: "{{ session('error_message') }}",
             });
         @endif

         // Laravel Validation Errors
         @if ($errors->any())
             @foreach ($errors->all() as $error)
                 iziToast.error({
                     title: "{{ labels('admin_labels.validation_error', 'Validation Error') }}",
                     message: "{{ $error }}",
                 });
             @endforeach
         @endif
     });
 </script>

 </body>

 </html>
