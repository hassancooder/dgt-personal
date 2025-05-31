AOS.init({
    once: true,
    offset: 100,
    easing: 'ease-out',
});
function togglePassInput(iconEl, inputSelector) {
    const input = $(inputSelector);
    const icon = $(iconEl);

    if (input.attr('type') === 'password') {
        input.attr('type', 'text');
        icon.removeClass('ri-eye-line').addClass('ri-eye-off-line');
    } else {
        input.attr('type', 'password');
        icon.removeClass('ri-eye-off-line').addClass('ri-eye-line');
    }
}

function showToast() {
    const $toast = $('#custom-toast');
    $toast.removeClass('bottom-[-100px] opacity-0').addClass('bottom-6 opacity-100');

    setTimeout(() => {
        hideToast();
    }, 3000);
}

function hideToast() {
    const $toast = $('#custom-toast');
    $toast.removeClass('bottom-6 opacity-100').addClass('bottom-[-100px] opacity-0');
}
function redirect($url) {
    window.location.href = $url;
}
$(window).on('load', function () {
    if ($('#custom-toast').length) {
        showToast();
    }
});



