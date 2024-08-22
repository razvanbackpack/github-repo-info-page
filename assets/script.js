$(document).ready(function() {
    let isFormatted = true;

    // Initialize marked
    marked.setOptions({
        breaks: true,
        gfm: true,
        highlight: function(code, lang) {
            if (lang && hljs.getLanguage(lang)) {
                return hljs.highlight(code, { language: lang }).value;
            } else {
                return hljs.highlightAuto(code).value;
            }
        }
    });

    function displayFormattedReadme() {
        $('#readme-content').html(marked.parse($('#readme-content').text()));
        hljs.highlightAll();
        isFormatted = true;
    }

    function displayRawReadme() {
        $('#readme-content').html('<pre><code>' + escapeHtml($('#readme-content').text()) + '</code></pre>');
        isFormatted = false;
    }

    $('#toggle-readme').click(function() {
        if (isFormatted) {
            displayRawReadme();
        } else {
            displayFormattedReadme();
        }
    });

    // Initial formatting of README
    displayFormattedReadme();

    // Helper function to escape HTML
    function escapeHtml(unsafe) {
        return unsafe
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }
});