// GCSE/pages/EnglishPractice/script.js
console.log('Script.js loaded!');

/**
 * Function to safely get elements by ID
 */
function getElement(id) {
    const element = document.getElementById(id);
    // Only log error if element is absolutely expected
    // if (!element && id !== 'optionalElement') { console.error(`Element #${id} not found.`); }
    return element;
}


/**
 * Adds event listeners when the DOM is ready.
 */
function initializeEnglishPractice() {
    console.log("Initializing English Practice script...");

    // --- Add Extra Item Button Functionality ---
    const addExtraItemButtons = document.querySelectorAll('.add-extra-item');
    if (addExtraItemButtons.length > 0) {
        addExtraItemButtons.forEach(button => {
            button.removeEventListener('click', handleAddExtraItem); // Remove old listener just in case
            button.addEventListener('click', handleAddExtraItem);
        });
        console.log(`Added listener to ${addExtraItemButtons.length} 'Add Extra Item' buttons.`);
    }

    // --- Daily Entry Form Validation ---
    const dailyForm = getElement('dailyEntryForm');
     if (dailyForm) {
        dailyForm.removeEventListener('submit', handleEntryFormValidation); // Remove old if exists
        dailyForm.addEventListener('submit', handleEntryFormValidation);
        console.log("Added validation listener to daily entry form.");
     }


    // --- Flashcard Functionality Initialization ---
    // Check if required elements for flashcard exist on the current page
    if (getElement('flashcard-term') && typeof practiceItems !== 'undefined') {
         initializeFlashcards();
    }

    // --- Favorite Functionality Initialization ---
/**
 * Handle clicking the "Add Extra Item" button on the daily entry form.
 */
function handleAddExtraItem() {
     console.log("Add extra item clicked");
     const button = this; // The clicked button
     const categoryId = button.getAttribute('data-category-id');
     let nextIndex = parseInt(button.getAttribute('data-next-index'));
     const cardBody = button.closest('.card-body');
     if (!cardBody || !categoryId || isNaN(nextIndex)) {
          console.error("Cannot add extra item: Missing data attributes or parent element.");
          return;
     }

     const placeholderCatNameElement = cardBody.closest('.card').querySelector('.card-header h2, .card-header h6'); // Adjust selector if header changed
     const placeholderCatName = placeholderCatNameElement ? placeholderCatNameElement.textContent.trim() : 'Category';


    const newItemHtml = `
    <div class="row gx-2 mb-2 pb-2 entry-item border-top pt-2">
        <div class="col-md-1 pt-1 text-end pe-0 text-muted d-none d-md-block">
            <small>Extra:</small>
        </div>
        <div class="col-12 col-md-11">
            <div class="mb-1">
                <label for="item_title_${categoryId}_${nextIndex}" class="form-label visually-hidden">Extra Title/Word ${nextIndex}</label>
                <input type="text" class="form-control form-control-sm" id="item_title_${categoryId}_${nextIndex}" name="items[${categoryId}][${nextIndex}][title]" placeholder="Extra Title/Word ${nextIndex} for ${placeholderCatName}">
                <div class="invalid-feedback">Required.</div>
            </div>
            <div class="mb-1">
                 <label for="item_meaning_${categoryId}_${nextIndex}" class="form-label visually-hidden">Extra Meaning/Rule ${nextIndex}</label>
                <textarea class="form-control form-control-sm" id="item_meaning_${categoryId}_${nextIndex}" name="items[${categoryId}][${nextIndex}][meaning]" rows="1" placeholder="Extra Meaning / Rule"></textarea>
                <div class="invalid-feedback">Required.</div>
            </div>
            <div>
                 <label for="item_example_${categoryId}_${nextIndex}" class="form-label visually-hidden">Extra Example ${nextIndex}</label>
                <textarea class="form-control form-control-sm" id="item_example_${categoryId}_${nextIndex}" name="items[${categoryId}][${nextIndex}][example]" rows="1" placeholder="Extra Example Sentence"></textarea>
                <div class="invalid-feedback">Required.</div>
            </div>
        </div>
    </div>`;

    // Insert the new fields *before* the button
    button.insertAdjacentHTML('beforebegin', newItemHtml);

    // Focus the first input of the newly added item
    const firstNewInput = getElement(`item_title_${categoryId}_${nextIndex}`);
    if (firstNewInput) {
        firstNewInput.focus();
    }

    // Update the button's index for the next potential click
    button.setAttribute('data-next-index', nextIndex + 1);
 }


/**
 * Basic frontend validation for the daily entry form.
 */
function handleEntryFormValidation(event) {
    const form = event.target;
    if (!form.checkValidity()) {
      event.preventDefault();
      event.stopPropagation();
      console.log("Daily entry form invalid based on HTML5 validation.");
    } else {
        console.log("Daily entry form seems valid (HTML5), submitting...");
        // Optional: Add a loading indicator here
    }
    form.classList.add('was-validated'); // Show validation styles regardless
}


/**
 * Initialize flashcard page elements and listeners.
 */
function initializeFlashcards() {
    const flashcardDiv = getElement('flashcard');
    const flashcardRevealButton = getElement('flashcard-reveal');
    const flashcardDetails = getElement('flashcard-details');
    const flashcardNextButton = getElement('flashcard-next');
    const flashcardTerm = getElement('flashcard-term');
    const progressBar = document.querySelector('.flashcard-progress .progress-bar'); // Target more specific class

    if (!flashcardTerm || !flashcardRevealButton || !flashcardDetails || !flashcardNextButton || !progressBar || typeof practiceItems === 'undefined' || typeof totalItems === 'undefined') {
        console.error("Flashcard initialization failed: Missing elements or data.");
        return; // Exit if essential parts are missing
    }
     console.log(`Initializing flashcards. Found ${totalItems -1} remaining items.`);


    let currentIndex = 0; // Index for the 'remainingItems' (practiceItems) array

    // Function to update card content
    function updateCard(item) {
        flashcardTerm.textContent = item.item_title;
        flashcardDetails.innerHTML = `
            <p class="mb-2"><strong>Meaning/Rule:</strong><br>${item.item_meaning.replace(/\n/g, '<br>')}</p>
            <p class="mb-2"><strong>Example:</strong><br>${item.item_example.replace(/\n/g, '<br>')}</p>
            <p class="text-muted mb-0"><small>Category: ${item.category_name}</small></p>
        `;
        // Reset display
        flashcardDetails.style.display = 'none';
        flashcardRevealButton.style.display = 'inline-block';
        flashcardNextButton.style.display = 'none';
        flashcardDiv.style.opacity = '1'; // Fade in
    }

    // --- Event Listeners ---
    flashcardRevealButton.addEventListener('click', () => {
        flashcardDetails.style.display = 'block';
        flashcardRevealButton.style.display = 'none';
        flashcardNextButton.style.display = 'inline-block';
        // Focus next button for keyboard nav
        setTimeout(() => flashcardNextButton.focus(), 50);
    });

    flashcardNextButton.addEventListener('click', function handleNextClick() {
        if (currentIndex < practiceItems.length) {
            const nextItem = practiceItems[currentIndex];
            flashcardDiv.style.opacity = '0'; // Start fade out

            setTimeout(() => {
                updateCard(nextItem);
                 // Update progress bar (currentIndex is 0-based for the array, add 2 for display count)
                const currentCount = currentIndex + 2;
                const progress = (currentCount / totalItems) * 100;
                progressBar.style.width = `${progress}%`;
                progressBar.textContent = `${currentCount}/${totalItems}`;
                progressBar.setAttribute('aria-valuenow', currentCount);
            }, 200); // Wait for fade out

             currentIndex++; // Increment index for the *next* click

        } else {
            // --- End of practice ---
            flashcardDiv.style.opacity = '0';
            setTimeout(() => {
                 flashcardDiv.innerHTML = `
                    <div class="card-body text-center p-4">
                        <h3 class="text-success"><i class="fas fa-check-circle fa-2x mb-3"></i></h3>
                        <h3>Practice Complete!</h3>
                        <p class="mb-4">You've reviewed all ${totalItems} items.</p>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="practice.php?category=${currentCategory || ''}" class="btn btn-primary">
                                <i class="fas fa-redo me-1"></i>Practice Again ${currentCategory ? '(Same Category)' : '(All)'}
                            </a>
                            <a href="review.php" class="btn btn-outline-secondary">
                                <i class="fas fa-list-alt me-1"></i>Review Entries
                            </a>
                        </div>
                    </div>
                `;
                flashcardDiv.style.opacity = '1';
                // Optionally update progress bar to 100%
                progressBar.style.width = `100%`;
                progressBar.textContent = `Complete! ${totalItems}/${totalItems}`;
                progressBar.classList.add('bg-success'); // Change color
                progressBar.setAttribute('aria-valuenow', totalItems);
             }, 300);
             // Remove keyboard listener? Maybe not necessary if element is removed.
        }
    });

     // Keyboard Shortcuts (attach to document)
    let spacebarHandler = function(event) {
        if (event.code === 'Space' || event.keyCode === 32) {
             if (flashcardRevealButton && flashcardRevealButton.style.display !== 'none') {
                event.preventDefault(); // Prevent page scroll
                flashcardRevealButton.click();
             }
        }
    };
    let arrowRightHandler = function(event) {
         if (event.code === 'ArrowRight' || event.keyCode === 39) {
             if (flashcardNextButton && flashcardNextButton.style.display !== 'none') {
                 flashcardNextButton.click();
             }
        }
    };

     document.removeEventListener('keydown', spacebarHandler); // Remove old listener first
     document.removeEventListener('keydown', arrowRightHandler); // Remove old listener first
     document.addEventListener('keydown', spacebarHandler);
     document.addEventListener('keydown', arrowRightHandler);
     console.log("Added keyboard listeners for flashcards.");


    // Initial progress bar state
    progressBar.style.width = `${(1 / totalItems) * 100}%`;
    progressBar.textContent = `1/${totalItems}`;
    progressBar.setAttribute('aria-valuemax', totalItems);
    progressBar.setAttribute('aria-valuenow', 1);

} // End initializeFlashcards

// Handle favorite toggling
function initializeFavorites() {
    document.querySelectorAll('.toggle-favorite').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const itemId = this.dataset.itemId;
            const icon = this.querySelector('i');
            
            console.log('Favorite button clicked for item:', itemId);
            
            // Send AJAX request
            fetch('toggle_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'item_id=' + itemId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update button appearance
                    this.classList.toggle('btn-warning');
                    this.classList.toggle('btn-outline-warning');
                    icon.classList.toggle('far');
                    icon.classList.toggle('fas');
                    
                    // Update title
                    this.title = data.is_favorited ? 'Remove from Favorites' : 'Add to Favorites';
                    
                    // Show toast notification
                    showToast(data.message);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error toggling favorite', 'error');
            });
        });
    });
}

// Show toast message
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : 'success'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    document.body.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remove toast after it's hidden
    toast.addEventListener('hidden.bs.toast', function() {
        document.body.removeChild(toast);
    });
}

// --- Run Initializer ---
// Use DOMContentLoaded to ensure HTML is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeEnglishPractice);
} else {
    initializeEnglishPractice(); // DOM already loaded
}