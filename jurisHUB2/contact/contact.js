document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("form");
    const emailInput = document.getElementById("email");
    const messageInput = document.getElementById("message");

    form.addEventListener("submit", function (event) {
        if (!validateEmail(emailInput.value)) {
            event.preventDefault();
            alert("Veuillez entrer une adresse email valide.");
            emailInput.focus();
        } else if (messageInput.value.trim() === "") {
            event.preventDefault();
            alert("Veuillez entrer un message.");
            messageInput.focus();
        } else {
            alert("Votre message a été envoyé avec succès !");
        }
    });

    function validateEmail(email) {
        const regex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        return regex.test(email);
    }
});
