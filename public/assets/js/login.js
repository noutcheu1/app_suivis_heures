const form = document.getElementById("create_login_form");

const typeInput = document.getElementById("type");
const idConexion = document.getElementById("id_conexion");
const passwordBtn = document.getElementById("password");
const passwordVerifyBtn = document.getElementById("password2");

form.addEventListener("submit", login_js);

function login_js(e) {
    e.preventDefault(); // ⬅️ essentiel

    const type = typeInput.value.trim();
    const id = idConexion.value.trim();
    const password = passwordBtn.value;
    const password2 = passwordVerifyBtn.value;

    if (!id || !password || !password2) {
        toast("Tous les champs sont obligatoires");
        return;
    }

    fetch(`/api/register?type=${type}`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify({ id, password, password2 })
    })
    .then(async res => {
        const text = await res.text();
        console.log("API RAW:", text);

        try {
            return JSON.parse(text);
        } catch {
            throw new Error("Réponse API invalide");
        }
    })
    .then(data => {
        if (data.success) {
            window.location.href = "/login";
        } else {
            toast(data.error, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        toast(err.message);
    });

}
