document.addEventListener('DOMContentLoaded', function() {
  const wrapper = document.querySelector('.table-wrapper');
  let isDown = false;
  let startX;
  let scrollLeft;
  wrapper.addEventListener('mousedown', function(e) {
    isDown = true;
    startX = e.pageX - wrapper.offsetLeft;
    scrollLeft = wrapper.scrollLeft;
  });
  wrapper.addEventListener('mouseup', function() {
    isDown = false;
  });
  wrapper.addEventListener('mouseleave', function() {
    isDown = false;
  });
  wrapper.addEventListener('mousemove', function(e) {
    if (!isDown) return;
    e.preventDefault();
    const x = e.pageX - wrapper.offsetLeft;
    const walk = (x - startX);
    wrapper.scrollLeft = scrollLeft - walk;
  });
});