(function(){
  function ready(fn){ if(document.readyState!=="loading"){ fn(); } else { document.addEventListener("DOMContentLoaded", fn); } }
  ready(function(){
    var boxes = document.querySelectorAll(".ty-mp4-embed");
    if(!boxes || !boxes.length) return;
    boxes.forEach(function(box){
      var video = box.querySelector("video");
      var overlay = box.querySelector(".ty-mp4-overlay");
      var sources = [];
      var sourcesAttr = box.getAttribute("data-sources");
      if(sourcesAttr){
        try {
          var parsed = JSON.parse(sourcesAttr);
          if(Array.isArray(parsed)) sources = parsed;
        } catch(e){}
      }
      if(!sources.length){
        var single = box.getAttribute("data-src") || "";
        if(single) sources = [single];
      }
      var lineButtons = box.querySelectorAll(".ty-mp4-line");
      var activeIndex = 0;
      var hasAutoDetected = false;
      var detectInProgress = false;
      var hasStarted = false;
      var poster = box.getAttribute("data-poster") || "";
      if(poster) video.setAttribute("poster", poster);

      function updateButtons(){
        if(!lineButtons || !lineButtons.length) return;
        lineButtons.forEach(function(btn){
          var idx = parseInt(btn.getAttribute("data-index"), 10);
          if(idx === activeIndex){
            btn.classList.add("is-active");
          } else {
            btn.classList.remove("is-active");
          }
        });
      }

      function setActive(index){
        if(index < 0 || index >= sources.length) return;
        activeIndex = index;
        updateButtons();
      }

      function applySource(index){
        var src = sources[index] || "";
        if(!src) return false;
        if(video.getAttribute("src") !== src){
          video.setAttribute("src", src);
          video.load();
        }
        return true;
      }

      function playCurrent(){
        if(!applySource(activeIndex)) return;
        video.setAttribute("controls", "controls");
        var p = video.play();
        if(p && typeof p.catch === "function"){
          p.catch(function(e){ /* 某些浏览器可能仍需点击原生播放按钮 */ });
        }
        if(overlay){ overlay.classList.add("hidden"); }
        hasStarted = true;
      }

      function attemptSource(index, cb){
        if(!applySource(index)){
          cb(false);
          return;
        }
        video.setAttribute("controls", "controls");
        var settled = false;
        var timer = setTimeout(function(){ finish(false); }, 5000);

        function cleanup(){
          clearTimeout(timer);
          video.removeEventListener("error", onError);
          video.removeEventListener("loadedmetadata", onReady);
          video.removeEventListener("canplay", onReady);
        }
        function finish(ok){
          if(settled) return;
          settled = true;
          cleanup();
          cb(ok);
        }
        function onError(){ finish(false); }
        function onReady(){ finish(true); }

        video.addEventListener("error", onError);
        video.addEventListener("loadedmetadata", onReady);
        video.addEventListener("canplay", onReady);

        var p = video.play();
        if(p && typeof p.catch === "function"){
          p.catch(function(e){ /* 某些浏览器可能仍需点击原生播放按钮 */ });
        }
      }

      function autoDetectAndPlay(){
        if(hasAutoDetected || sources.length <= 1){
          playCurrent();
          return;
        }
        if(detectInProgress) return;
        hasAutoDetected = true;
        detectInProgress = true;
        if(overlay){ overlay.classList.add("hidden"); }

        var startIndex = activeIndex;
        var tried = 0;

        function next(ok){
          if(ok){
            detectInProgress = false;
            hasStarted = true;
            return;
          }
          tried++;
          if(tried >= sources.length){
            detectInProgress = false;
            if(overlay){ overlay.classList.remove("hidden"); }
            return;
          }
          var idx = (startIndex + tried) % sources.length;
          setActive(idx);
          attemptSource(idx, next);
        }

        setActive(startIndex);
        attemptSource(startIndex, next);
      }

      function start(){
        if(!sources.length) return;
        if(!hasAutoDetected && sources.length > 1){
          autoDetectAndPlay();
          return;
        }
        playCurrent();
      }

      if(lineButtons && lineButtons.length){
        lineButtons.forEach(function(btn){
          btn.addEventListener("click", function(e){
            e.preventDefault();
            e.stopPropagation();
            var idx = parseInt(btn.getAttribute("data-index"), 10);
            if(isNaN(idx)) return;
            setActive(idx);
            if(hasStarted || video.getAttribute("src")){
              playCurrent();
            }
          });
        });
      }

      if(overlay){
        overlay.addEventListener("click", function(e){
          e.preventDefault();
          e.stopPropagation();
          start();
        });
        overlay.addEventListener("keydown", function(e){
          if(e.key === "Enter" || e.key === " "){
            e.preventDefault();
            e.stopPropagation();
            start();
          }
        });
      }
      // 点击视频区域也可触发首次播放
      box.addEventListener("click", function(e){
        if(e.target === video && !video.paused) return;
        if(e.target && e.target.classList && e.target.classList.contains("ty-mp4-line")) return;
        if(!video.getAttribute("src")){
          e.preventDefault();
          start();
        }
      });
    });
  });
})();
