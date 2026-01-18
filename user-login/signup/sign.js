const eyepass = document.getElementById("eyepass");
const eyecpass = document.getElementById("eyecpass");
const signpassword = document.getElementById("signpassword");
const signconfirmpassword = document.getElementById("signconformpassword");
const form = document.querySelector('.singup');
const statusBox = document.querySelector('.status');

// Add smooth page transition for login link
const loginLink = document.querySelector('.account a[href="Login.html"]');
if (loginLink) {
    loginLink.addEventListener('click', (event) => {
        event.preventDefault();
        document.body.classList.add('page-out');
        setTimeout(() => {
            window.location.href = 'Login.html';
        }, 400);
    });
}

// Toggle main password visibility
if (eyepass && signpassword) {
    eyepass.addEventListener("click", () => {
        const isHidden = signpassword.type === "password";
        signpassword.type = isHidden ? "text" : "password";
        eyepass.src = isHidden ? "eye/eye-on.svg" : "eye/eye-off.svg";
    });
}

// Toggle confirm password visibility
if (eyecpass && signconfirmpassword) {
    eyecpass.addEventListener("click", () => {
        const isHidden = signconfirmpassword.type === "password";
        signconfirmpassword.type = isHidden ? "text" : "password";
        eyecpass.src = isHidden ? "eye/eye-on.svg" : "eye/eye-off.svg";
    });
}

if (form) {
    form.addEventListener('register', (event) => {
        event.preventDefault();
        const username = form.elements.username.value.trim();
        const pwd = form.elements.password.value.trim();

        if (!username && !pwd) {
            if (statusBox) statusBox.textContent = '! Please enter username and password.';
                return;
            }

        else if(!username) {
            if (statusBox) statusBox.textContent = '! Please enter username.';
            return;
        }
        else if(!pwd) {
            if (statusBox) statusBox.textContent = '! Please enter password.';
            return;
        }

        if (statusBox) statusBox.textContent = 'Login.';
    });
}
