const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#passwordInput');

    togglePassword.addEventListener('click', function () {
        // Alternar el atributo type
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);

        // Alternar el icono de ojo abierto/tachado
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });