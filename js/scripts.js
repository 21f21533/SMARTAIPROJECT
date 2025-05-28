// scripts.js

// Toggle dark mode (optional feature)
document.getElementById("darkToggle")?.addEventListener("click", function () {
    document.body.classList.toggle("dark-mode");
});

// Show/hide task phases (if dashboard includes them)
document.querySelectorAll(".toggle-phases").forEach(button => {
    button.addEventListener("click", () => {
        const taskId = button.getAttribute("data-task-id");
        const phasesEl = document.getElementById("phases-" + taskId);
        if (phasesEl) {
            phasesEl.classList.toggle("hidden");
        }
    });
});

// Confirm deletion
document.querySelectorAll(".delete-task-btn").forEach(btn => {
    btn.addEventListener("click", e => {
        if (!confirm("Are you sure you want to delete this task?")) {
            e.preventDefault();
        }
    });
});
