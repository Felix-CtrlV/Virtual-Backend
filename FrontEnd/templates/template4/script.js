document.addEventListener("DOMContentLoaded", function () {
  const getButtons = () => document.querySelectorAll(".btn-category");
  const itemsContainer = document.querySelector(".product-grid");
  const itemsSelector = ".product-item";
  const loadBtn = document.getElementById("load-more-btn");
  let activeFilter = "all";
  let noResultsEl = null; // Store the message element

  // 1. Filter Logic
  function applyFilter(filter) {
    activeFilter = filter || "all";
    const normFilter = (activeFilter || "").toString().toLowerCase().trim();

    // Toggle active class on buttons
    getButtons().forEach((b) => {
      const btnFilter = (b.dataset.filter || "")
        .toString()
        .toLowerCase()
        .trim();
      b.classList.toggle("active", btnFilter === normFilter);
    });

    let visibleCount = 0;

    // Loop through products and show/hide based on category
    document.querySelectorAll(itemsSelector).forEach((it) => {
      const cat = (it.dataset.category || "").toString().toLowerCase().trim();

      // If filter is 'all', show everything. Otherwise, match exact category name.
      const show = normFilter === "all" || cat === normFilter;

      it.style.display = show ? "" : "none";
      if (show) visibleCount++;
    });

    // 2. Handle "No Results" Message
    if (!noResultsEl) {
      noResultsEl = document.createElement("div");
      noResultsEl.className = "no-results text-center mt-4 alert alert-warning";
      // Show a concise "None" message when a category has no products
      noResultsEl.textContent = "None";
      noResultsEl.style.display = "none";
      // Insert it after the product grid
      itemsContainer.parentNode.insertBefore(
        noResultsEl,
        itemsContainer.nextSibling
      );
    }

    // Toggle the message display
    noResultsEl.style.display = visibleCount === 0 ? "block" : "none";
  }

  // 3. Click Event for Categories
  const catFilterContainer = document.querySelector(".category-filter");
  if (catFilterContainer) {
    catFilterContainer.addEventListener("click", function (e) {
      const btn = e.target.closest(".btn-category");
      if (!btn) return;

      const f = btn.dataset.filter || "all";
      applyFilter(f);
    });
  }

  // 4. Load More Logic (Preserved from your code)
  if (loadBtn) {
    loadBtn.addEventListener("click", async function () {
      let offset = parseInt(loadBtn.dataset.offset || "0", 10);
      const supplier = parseInt(loadBtn.dataset.supplier || "0", 10);

      loadBtn.disabled = true;
      const prevText = loadBtn.textContent;
      loadBtn.textContent = "LOADING...";

      try {
        const form = new FormData();
        form.append("offset", offset);
        form.append("supplier_id", supplier);

        const res = await fetch("../fetch_products.php", {
          method: "POST",
          body: form,
        });

        const text = await res.text();

        if (text.trim() === "NO_MORE") {
          loadBtn.textContent = "NO MORE PRODUCTS";
          loadBtn.disabled = true;
        } else {
          // Append new items
          const temp = document.createElement("div");
          temp.innerHTML = text;
          while (temp.firstChild) itemsContainer.appendChild(temp.firstChild);

          offset += 6;
          loadBtn.dataset.offset = offset;
          loadBtn.disabled = false;
          loadBtn.textContent = prevText;

          // IMPORTANT: Re-apply the current filter to the newly loaded items
          applyFilter(activeFilter);
        }
      } catch (err) {
        console.error(err);
        loadBtn.textContent = prevText;
        loadBtn.disabled = false;
      }
    });
  }

  // Initialize with All
  applyFilter("all");
});
