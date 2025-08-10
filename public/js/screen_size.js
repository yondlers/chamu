
function adjustTableVisibility() {
    // const table = document.querySelector('table');
    const table = document.getElementById('display_large');
    const cards = document.getElementById('display_small');


    if (window.innerWidth < 768) {

        // Small screen (sm)
        // table.classList.add('hidden');
        // table.classList.remove('table');
        //
        // cards.classList.remove('hidden');

        table.style.display = 'none';
        cards.style.display = 'block';

        alert(1);
    } else {
        // Medium (md) or larger
        // table.classList.remove('hidden');
        // table.classList.add('table');

        // cards.classList.add('hidden');

        // table.style.display = 'block';
        // cards.style.display = 'none';

        alert(2);
    }
}

// Run on initial load
adjustTableVisibility();

// Add a resize event listener to handle dynamic changes
window.addEventListener('resize', adjustTableVisibility);

