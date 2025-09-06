$(function() {
    "use strict";

    let originalButtonText;

    $("#carrierForm").on("submit", function(e) {
        e.preventDefault();
        const form = $(this);

        // Client-side validation
        if (!validateForm(form)) {
            return false;
        }

        const formArray = {
            formId: form.attr("id"),
            csrf: form.find('input[name="_token"]').val(),
            url: form.closest('form').attr('action'),
            formObject : form,
        };
        ajaxRequest(formArray);
    });

    function disableSubmitButton(form) {
        originalButtonText = form.find('button[type="submit"]').text();
        form.find('button[type="submit"]')
            .prop('disabled', true)
            .html('  <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Loading...');
    }

    function enableSubmitButton(form) {
        form.find('button[type="submit"]')
            .prop('disabled', false)
            .html(originalButtonText);
    }

    function beforeCallAjaxRequest(formObject){
        disableSubmitButton(formObject);
    }
    function afterCallAjaxResponse(formObject){
        enableSubmitButton(formObject);
    }
    function afterSeccessOfAjaxRequest(formObject){
        formAdjustIfSaveOperation(formObject);
        pageRedirect(formObject);
    }
    function pageRedirect(formObject){
        var redirectTo = '/carrier/list';
        setTimeout(function() {
           location.href = baseURL + redirectTo;
        }, 1000);
    }

    function ajaxRequest(formArray){
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
                // Actions to be performed before sending the AJAX request
                if (typeof beforeCallAjaxRequest === 'function') {
                    beforeCallAjaxRequest(formArray.formObject);
                }
            },
        });
        jqxhr.done(function(data) {
            iziToast.success({title: 'Success', layout: 2, message: data.message});
            // Actions to be performed after response from the AJAX request
            if (typeof afterSeccessOfAjaxRequest === 'function') {
                afterSeccessOfAjaxRequest(formArray.formObject);
            }
        });
        jqxhr.fail(function(response) {
                var message = response.responseJSON.message;
                iziToast.error({title: 'Error', layout: 2, message: message});
        });
        jqxhr.always(function() {
            // Actions to be performed after the AJAX request is completed, regardless of success or failure
            if (typeof afterCallAjaxResponse === 'function') {
                afterCallAjaxResponse(formArray.formObject);
            }
        });
    }

    function formAdjustIfSaveOperation(formObject){
        const _method = formObject.find('input[name="_method"]').val();
        /* Only if Save Operation called*/
        if(_method.toUpperCase() == 'POST' ){
            var formId = formObject.attr("id");
            $("#"+formId)[0].reset();
        }
    }

    function validateForm(form) {
        let isValid = true;

        // Clear previous error messages
        form.find('.error-message').remove();
        form.find('.is-invalid').removeClass('is-invalid');

        // Validate email (must be gmail.com)
        const email = form.find('input[name="email"]').val();
        if (email && email.length > 0) {
            const emailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
            if (!emailRegex.test(email)) {
                showFieldError(form.find('input[name="email"]'), 'يجب أن يكون البريد الإلكتروني من Gmail فقط (مثال: user@gmail.com)');
                isValid = false;
            }
        }

        // Validate phone numbers (mobile, phone, whatsapp)
        const phoneFields = ['mobile', 'phone', 'whatsapp'];
        phoneFields.forEach(function(fieldName) {
            const field = form.find(`input[name="${fieldName}"]`);
            const value = field.val();
            if (value && value.length > 0) {
                const phoneRegex = /^[0-9]{8,}$/;
                if (!phoneRegex.test(value)) {
                    let fieldLabel = fieldName === 'mobile' ? 'رقم الهاتف المحمول' :
                                   fieldName === 'phone' ? 'رقم الهاتف' : 'رقم الواتس اب';
                    showFieldError(field, `${fieldLabel} يجب أن يكون أرقاماً فقط ولا يقل عن 8 أرقام`);
                    isValid = false;
                }
            }
        });

        return isValid;
    }

    function showFieldError(field, message) {
        field.addClass('is-invalid');
        field.after(`<div class="error-message text-danger small mt-1">${message}</div>`);
    }

    // Real-time validation
    $(document).ready(function() {
        // Email validation on blur
        $('input[name="email"]').on('blur', function() {
            const email = $(this).val();
            $(this).removeClass('is-invalid');
            $(this).siblings('.error-message').remove();

            if (email && email.length > 0) {
                const emailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
                if (!emailRegex.test(email)) {
                    showFieldError($(this), 'يجب أن يكون البريد الإلكتروني من Gmail فقط (مثال: user@gmail.com)');
                }
            }
        });

        // Phone validation on blur
        const phoneFields = ['mobile', 'phone', 'whatsapp'];
        phoneFields.forEach(function(fieldName) {
            $(`input[name="${fieldName}"]`).on('blur', function() {
                const value = $(this).val();
                $(this).removeClass('is-invalid');
                $(this).siblings('.error-message').remove();

                if (value && value.length > 0) {
                    const phoneRegex = /^[0-9]{8,}$/;
                    if (!phoneRegex.test(value)) {
                        let fieldLabel = fieldName === 'mobile' ? 'رقم الهاتف المحمول' :
                                       fieldName === 'phone' ? 'رقم الهاتف' : 'رقم الواتس اب';
                        showFieldError($(this), `${fieldLabel} يجب أن يكون أرقاماً فقط ولا يقل عن 8 أرقام`);
                    }
                }
            });

            // Prevent non-numeric input in phone fields
            $(`input[name="${fieldName}"]`).on('keypress', function(e) {
                // Allow backspace, delete, tab, escape, enter
                if ([8, 9, 27, 13, 46].indexOf(e.keyCode) !== -1 ||
                    // Allow Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                    (e.keyCode === 65 && e.ctrlKey === true) ||
                    (e.keyCode === 67 && e.ctrlKey === true) ||
                    (e.keyCode === 86 && e.ctrlKey === true) ||
                    (e.keyCode === 88 && e.ctrlKey === true)) {
                    return;
                }
                // Ensure that it is a number and stop the keypress
                if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                    e.preventDefault();
                }
            });
        });
    });


});
