window.load_navicons = function (e) {
    if (e && 2 === e.length) {
        var t = window,
            n = !(!t.document.createElementNS || !t.document.createElementNS("http://www.w3.org/2000/svg", "svg").createSVGRect || !document.implementation.hasFeature("http://www.w3.org/TR/SVG11/feature#Image", "1.1") || window.opera && -1 === navigator.userAgent.indexOf("Chrome")),
            o = function (o) {
                var r = t.document.createElement("link"),
                    a = t.document.getElementsByTagName("script")[0];
                r.rel = "stylesheet", r.href = e[o && n ? 0 : o ? 1 : 2], a.parentNode.insertBefore(r, a)
            }, r = new t.Image;
        r.onerror = function () {
            o(!1)
        }, r.onload = function () {
            o(1 === r.width && 1 === r.height)
        }, r.src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=="
    }
};
//load_navicons(["css/wl-icons/wl-icons.fallback.css", "css/wl-icons/wl-icons.svg.css"]);
load_navicons(["css/wl-icons/wl-icons.svg.css", "css/wl-icons/wl-icons.fallback.css"]);