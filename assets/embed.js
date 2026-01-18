(function(){
  function ready(fn){ if(document.readyState!=="loading"){ fn(); } else { document.addEventListener("DOMContentLoaded", fn); } }
  ready(function(){
    var boxes = document.querySelectorAll(".ty-mp4-embed");
    if(!boxes || !boxes.length) return;
    boxes.forEach(function(box){
      var video = box.querySelector("video");
      var overlay = box.querySelector(".ty-mp4-overlay");
      var src = box.getAttribute("data-src") || "";
      var poster = box.getAttribute("data-poster") || "";
      if(poster) video.setAttribute("poster", poster);

      function start(){
        if(!src) return;
        if(!video.getAttribute("src")) {
          video.setAttribute("src", src);
        }
        video.setAttribute("controls", "controls");
        var p = video.play();
        if(p && typeof p.catch === "function"){
          p.catch(function(e){ /* 某些浏览器可能仍需点击原生播放按钮 */ });
        }
        if(overlay){ overlay.classList.add("hidden"); }
      }

      if(overlay){
        overlay.addEventListener("click", start);
        overlay.addEventListener("keydown", function(e){
          if(e.key === "Enter" || e.key === " "){
            e.preventDefault(); start();
          }
        });
      }
      // 点击视频区域也可触发首次播放
      box.addEventListener("click", function(e){
        if(e.target === video && !video.paused) return;
        if(!video.getAttribute("src")) start();
      });
    });
  });
})();