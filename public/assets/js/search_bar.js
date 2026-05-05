const searchBar = document.getElementById('searchBar');

searchBar.addEventListener('keyup', filterIntervenants);

function filterIntervenants() {
    const value = searchBar.value.toLowerCase();
    const items = document.querySelectorAll(".search-item");
    let count = 0;

    items.forEach(item => {
        const name = item.querySelector(".name").textContent.toLowerCase();

        if (name.includes(value) && (count++ < 10 || value === "")) {
            item.style.display = "block";
        } else {
            item.style.display = "none";
        }
    });
}
