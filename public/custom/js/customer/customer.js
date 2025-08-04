$(function() {
    "use strict";

    let originalButtonText;
    const baseURL = $('#base_url').val(); // احصل على قيمة base_url من العنصر المخفي

    // Email validation function
    function validateEmail(email) {
        if (!email) {
            $('#emailError').text("البريد الإلكتروني مطلوب").show();
            $('input[name="email"]').addClass('is-invalid');
            return false;
        }

        // Strict check for .com ending
        const comPattern = /\.com$/i;
        if (!comPattern.test(email)) {
            $('#emailError').text("يجب أن ينتهي البريد الإلكتروني بـ .com").show();
            $('input[name="email"]').addClass('is-invalid');
            return false;
        }

        // Validate email format with .com ending
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.com$/i;
        if (!emailRegex.test(email)) {
            $('#emailError').text("صيغة البريد الإلكتروني غير صحيحة").show();
            $('input[name="email"]').addClass('is-invalid');
            return false;
        }

        $('#emailError').hide();
        $('input[name="email"]').removeClass('is-invalid');
        return true;
    }

    // Validate email on input
    $('input[name="email"]').on('input', function() {
        validateEmail($(this).val().trim());
    });

    // Validate form on submit
    $("#customerForm").on("submit", function(e) {
        e.preventDefault();
        const form = $(this);
        const email = $('input[name="email"]').val().trim();

        // التحقق من صحة الإيميل قبل الإرسال
        if (!validateEmail(email)) {
            return false;
        }

        const formArray = {
            formId: form.attr("id"),
            csrf: form.find('input[name="_token"]').val(),
            url: form.attr('action'),
            formObject: form,
        };
        ajaxRequest(formArray);
    });

    function disableSubmitButton(form) {
        originalButtonText = form.find('button[type="submit"]').text();
        form.find('button[type="submit"]')
            .prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');
    }

    function enableSubmitButton(form) {
        form.find('button[type="submit"]')
            .prop('disabled', false)
            .html(originalButtonText);
    }

    function beforeCallAjaxRequest(formObject) {
        disableSubmitButton(formObject);
    }

    function afterCallAjaxResponse(formObject) {
        enableSubmitButton(formObject);
    }

    function afterSeccessOfAjaxRequest(formObject) {
        formAdjustIfSaveOperation(formObject);
        pageRedirect(formObject);
    }

    function pageRedirect(formObject) {
        var redirectTo = '/customer/list';
        setTimeout(function() {
            location.href = baseURL + redirectTo;
        }, 1000);
    }

    function ajaxRequest(formArray) {
        var formData = new FormData(document.getElementById(formArray.formId));

        var jqxhr = $.ajax({
            type: 'POST',
            url: formArray.url,
            data: formData,
            dataType: 'json',
            contentType: false,
            processData: false,
            headers: {
                'X-CSRF-TOKEN': formArray.csrf
            },
            beforeSend: function() {
                if (typeof beforeCallAjaxRequest === 'function') {
                    beforeCallAjaxRequest(formArray.formObject);
                }
            },
        });

        jqxhr.done(function(data) {
            iziToast.success({
                title: 'Success',
                layout: 2,
                message: data.message
            });

            if (typeof afterSeccessOfAjaxRequest === 'function') {
                afterSeccessOfAjaxRequest(formArray.formObject);
            }
        });

        jqxhr.fail(function(response) {
            var message = response.responseJSON?.message || "{{ __('validation.unknown_error') }}";
            iziToast.error({
                title: 'Error',
                layout: 2,
                message: message
            });
        });

        jqxhr.always(function() {
            if (typeof afterCallAjaxResponse === 'function') {
                afterCallAjaxResponse(formArray.formObject);
            }
        });
    }

    function formAdjustIfSaveOperation(formObject) {
        const _method = formObject.find('input[name="_method"]').val();
        if (_method && _method.toUpperCase() == 'POST') {
            var formId = formObject.attr("id");
            $("#"+formId)[0].reset();
            // Define hideEmailError function
            $('#emailError').hide();
            $('input[name="email"]').removeClass('is-invalid');
        }
    }
});