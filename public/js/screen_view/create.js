
window.onload = function () {
    const selected_value = document.getElementById('rental_type').value;

    hideAll();

    if (selected_value === 'Whole') {
        showAll();
    }

};

document.getElementById('rental_type').addEventListener('change', function () {
    const selected_value = document.getElementById('rental_type').value;

    if (selected_value == "Whole") {
        showAll();
    } else {
        hideAll();
    }
});

function showAll () {
    document.getElementById('whole_display').style.display = 'block';
}

function hideAll () {
    document.getElementById('whole_display').style.display = 'none';
}
