document.addEventListener('DOMContentLoaded', function () {

    const button = document.querySelector('.waoc-whatsapp-btn');

    if (!button) {
        return;
    }

    button.addEventListener('click', function () {

        console.log('WhatsApp confirmation clicked');

    });

});