document.getElementById("reset-form").addEventListener("submit", function(event) {
    event.preventDefault(); // Empêche l'envoi du formulaire

    let emailInput = document.getElementById("email");
    let messageElement = document.querySelector(".message");

    if (validateEmail(emailInput.value)) {
        // Simuler l'envoi d'un email
        messageElement.style.color = "green";
        messageElement.textContent = "Un lien de réinitialisation a été envoyé à votre adresse email.";
        emailInput.value = ""; // Efface l'input après envoi
    } else {
        messageElement.style.color = "red";
        messageElement.textContent = "Veuillez entrer une adresse email valide.";
    }
});

// Fonction de validation d'email
function validateEmail(email) {
    let regex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    return regex.test(email);
}
