function sendHeight() {
    setTimeout(() => {
        var height = document.documentElement.scrollHeight;
        parent.postMessage({iframeHeight: height}, '*');
    }, 10);
}

window.onload = sendHeight;

var observer = new MutationObserver(sendHeight);
observer.observe(document.body, {childList: true, subtree: true, attributes: true});