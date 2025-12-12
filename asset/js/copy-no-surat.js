/* Centralized copy-to-clipboard + inline bubble notification
   Provides delegated handling for elements with class `copy-no-surat` and
   uses `data-nomor` attribute as the text to copy.
   Idempotent: sets window.__copyNoSuratBound to avoid double-binding.
*/
(function(){
    if (window.__copyNoSuratBound) return; window.__copyNoSuratBound = true;

    function removeExistingBubbles(){
        try { document.querySelectorAll('.copy-bubble').forEach(function(x){ x.parentNode && x.parentNode.removeChild(x); }); } catch(e){}
    }

    function showCopyBubble(el, text){
        try {
            removeExistingBubbles();
            var rect = el.getBoundingClientRect();
            var bubble = document.createElement('div');
            bubble.className = 'copy-bubble';
            bubble.textContent = text || 'Nomor telah disalin';
            Object.assign(bubble.style, {
                left: (rect.left + window.scrollX + rect.width/2) + 'px',
                top: (rect.top + window.scrollY - 8) + 'px',
                transform: 'translate(-50%, 0)'
            });
            document.body.appendChild(bubble);
            // trigger fade up
            requestAnimationFrame(function(){ setTimeout(function(){ bubble.style.transform = 'translate(-50%, -10px)'; bubble.style.opacity = '0'; }, 700); });
            setTimeout(function(){ if (bubble.parentNode) bubble.parentNode.removeChild(bubble); }, 1200);
        } catch(e) { try{ console && console.debug && console.debug('showCopyBubble error', e); }catch(_){} }
    }

    function fallbackCopy(text, el){
        try {
            var ta = document.createElement('textarea'); ta.value = text || '';
            ta.style.position = 'fixed'; ta.style.left = '-9999px'; document.body.appendChild(ta); ta.select();
            try { document.execCommand('copy'); showCopyBubble(el, 'Nomor telah disalin'); } catch(e) { showCopyBubble(el, 'Nomor telah disalin'); }
            document.body.removeChild(ta);
        } catch(e) {}
    }

    // Delegated handler
    document.addEventListener('click', function(e){
        var btn = (e.target && e.target.closest) ? e.target.closest('.copy-no-surat') : (function(){ var t=e.target; while(t && t!==document){ if(t.classList && t.classList.contains && t.classList.contains('copy-no-surat')) return t; t=t.parentNode;} return null; })();
        if (!btn) return;
        e.preventDefault();
        var nomor = btn.getAttribute('data-nomor') || '';
        if (!nomor) return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(nomor).then(function(){ showCopyBubble(btn, 'Nomor telah disalin'); }).catch(function(){ fallbackCopy(nomor, btn); });
        } else { fallbackCopy(nomor, btn); }
    }, false);

    // expose for other scripts if needed
    window.showCopyBubble = showCopyBubble;
    window.fallbackCopy = fallbackCopy;
})();
