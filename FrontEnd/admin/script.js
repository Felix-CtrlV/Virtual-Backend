document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.search input');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            console.log('Search:', e.target.value);
        });
    }
});

