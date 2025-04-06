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


    // --- Flashcard Functionality (Example Placeholder) ---
    const flashcardRevealButton = document.getElementById('flashcard-reveal');
    const flashcardDetails = document.getElementById('flashcard-details');
    const flashcardNextButton = document.getElementById('flashcard-next');
    // Add listeners if these elements exist on the practice page
    if (flashcardRevealButton && flashcardDetails) {
         flashcardRevealButton.addEventListener('click', () => {
            flashcardDetails.style.display = 'block'; // Show details
            flashcardRevealButton.style.display = 'none'; // Hide reveal button
             if(flashcardNextButton) flashcardNextButton.style.display = 'inline-block'; // Show next button
         });
          console.log("Added listener to flashcard reveal button.");
    }


    // Add other event listeners specific to English Practice pages here

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
             <span class="badge bg-secondary">${nextIndex}</span> <!-- Use badge for index -->
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

// Add other JavaScript functions needed for this feature below 