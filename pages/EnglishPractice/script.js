// GCSE/pages/EnglishPractice/script.js

document.addEventListener('DOMContentLoaded', function() {
    console.log("English Practice Script Loaded"); // Confirmation

    // --- Add Extra Item Functionality ---
    const addExtraItemButtons = document.querySelectorAll('.add-extra-item');
    if (addExtraItemButtons.length > 0) {
        addExtraItemButtons.forEach(button => {
            button.addEventListener('click', handleAddExtraItem);
        });
        console.log(`Added listener to ${addExtraItemButtons.length} extra item buttons.`);
    }

    // --- Flashcard Functionality ---
    initializeFlashcards();
});

// Function to handle adding extra item fields
function handleAddExtraItem() {
    console.log("Add extra item clicked");
    const button = this; // The clicked button
    const categoryId = button.getAttribute('data-category-id');
    const nextIndex = parseInt(button.getAttribute('data-next-index'));
    const cardBody = button.closest('.card-body');
    if (!cardBody || !categoryId || isNaN(nextIndex)) {
        console.error("Could not add extra item: Missing data attributes or parent element.");
        return;
    }

    const placeholderCatNameElement = cardBody.closest('.card').querySelector('.card-header h2');
    const placeholderCatName = placeholderCatNameElement ? placeholderCatNameElement.textContent : 'Category';

    const newItemHtml = `
    <div class="row g-2 mb-3 border-bottom pb-3 entry-item">
        <div class="col-md-1 pt-2 text-center text-muted d-none d-md-block">
            <span class="badge bg-secondary">${nextIndex}</span>
        </div>
        <div class="col-md-11">
            <div class="mb-2">
                <label for="item_title_${categoryId}_${nextIndex}" class="form-label visually-hidden">Title/Word</label>
                <input type="text" class="form-control form-control-sm" id="item_title_${categoryId}_${nextIndex}" name="items[${categoryId}][${nextIndex}][title]" placeholder="Extra Title/Word ${nextIndex} for ${placeholderCatName}" required>
                <div class="invalid-feedback">Please enter the title/word.</div>
            </div>
            <div class="mb-2">
                <label for="item_meaning_${categoryId}_${nextIndex}" class="form-label visually-hidden">Meaning/Rule</label>
                <textarea class="form-control form-control-sm" id="item_meaning_${categoryId}_${nextIndex}" name="items[${categoryId}][${nextIndex}][meaning]" rows="2" placeholder="Extra Meaning / Rule" required></textarea>
                <div class="invalid-feedback">Please enter the meaning/rule.</div>
            </div>
            <div class="mb-1">
                <label for="item_example_${categoryId}_${nextIndex}" class="form-label visually-hidden">Example</label>
                <textarea class="form-control form-control-sm" id="item_example_${categoryId}_${nextIndex}" name="items[${categoryId}][${nextIndex}][example]" rows="1" placeholder="Extra Example Sentence" required></textarea>
                <div class="invalid-feedback">Please enter an example sentence.</div>
            </div>
        </div>
    </div>`;

    // Insert the new fields before the button itself
    button.insertAdjacentHTML('beforebegin', newItemHtml);

    // Update the button's next index for the *next* click
    button.setAttribute('data-next-index', nextIndex + 1);
}

// Flashcard functionality
function initializeFlashcards() {
    const flashcardRevealButton = document.getElementById('flashcard-reveal');
    const flashcardDetails = document.getElementById('flashcard-details');
    const flashcardNextButton = document.getElementById('flashcard-next');
    const flashcardTerm = document.getElementById('flashcard-term');
    const progressBar = document.querySelector('.progress-bar');
    
    // Only initialize if we're on the practice page
    if (!flashcardRevealButton || !flashcardDetails) {
        return;
    }

    console.log("Initializing flashcard functionality");

    // Handle reveal button click
    flashcardRevealButton.addEventListener('click', () => {
        flashcardDetails.style.display = 'block';
        flashcardRevealButton.style.display = 'none';
        if(flashcardNextButton) {
            flashcardNextButton.style.display = 'inline-block';
        }
    });

    // Handle next button click
    if (flashcardNextButton && typeof practiceItems !== 'undefined') {
        let currentIndex = 0;
        const totalItems = practiceItems.length + 1; // +1 for the first item already displayed

        flashcardNextButton.addEventListener('click', function() {
            if (currentIndex < practiceItems.length) {
                const item = practiceItems[currentIndex];
                
                // Update flashcard content with animation
                flashcardTerm.style.opacity = '0';
                flashcardDetails.style.opacity = '0';
                
                setTimeout(() => {
                    // Update content
                    flashcardTerm.textContent = item.item_title;
                    flashcardDetails.innerHTML = `
                        <p><strong>Meaning:</strong> ${item.item_meaning}</p>
                        <p><strong>Example:</strong> ${item.item_example}</p>
                        <p class="text-muted"><small>Category: ${item.category_name}</small></p>
                    `;
                    
                    // Reset visibility
                    flashcardDetails.style.display = 'none';
                    flashcardRevealButton.style.display = 'inline-block';
                    flashcardNextButton.style.display = 'none';
                    
                    // Fade back in
                    flashcardTerm.style.opacity = '1';
                    flashcardDetails.style.opacity = '1';
                    
                    // Update progress
                    currentIndex++;
                    const progress = ((currentIndex + 1) / totalItems) * 100;
                    progressBar.style.width = `${progress}%`;
                    progressBar.textContent = `${currentIndex + 1}/${totalItems}`;
                }, 300);
                
            } else {
                // End of practice with animation
                const flashcard = document.querySelector('.flashcard');
                flashcard.style.opacity = '0';
                
                setTimeout(() => {
                    flashcard.innerHTML = `
                        <h3>Practice Complete!</h3>
                        <p class="mb-4">You've reviewed all ${totalItems} items.</p>
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="practice.php" class="btn btn-primary">
                                <i class="fas fa-redo me-2"></i>Practice Again
                            </a>
                            <a href="practice.php?category=${currentCategory}" class="btn btn-outline-primary">
                                <i class="fas fa-filter me-2"></i>Same Category
                            </a>
                        </div>
                    `;
                    flashcard.style.opacity = '1';
                }, 300);
            }
        });
    }
}

// Add keyboard shortcuts for flashcard navigation
document.addEventListener('keydown', function(event) {
    const flashcardReveal = document.getElementById('flashcard-reveal');
    const flashcardNext = document.getElementById('flashcard-next');
    
    if (event.code === 'Space' && flashcardReveal && flashcardReveal.style.display !== 'none') {
        event.preventDefault(); // Prevent page scroll
        flashcardReveal.click();
    } else if (event.code === 'ArrowRight' && flashcardNext && flashcardNext.style.display !== 'none') {
        flashcardNext.click();
    }
});

// Add other JavaScript functions needed for this feature below 