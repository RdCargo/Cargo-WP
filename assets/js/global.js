function ToggleLoading(bool,elem){
    if ( bool ) {
        const set_for = elem !== null ? '#wpwrap' : `#${elem}`;
        if($('#loader').length == 0){
            $(set_for).append(`<div id="loader"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" style="margin: auto; background: none; display: block; shape-rendering: auto;" width="84px" height="84px" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid"><g transform="translate(50 50)"> <g transform="scale(0.7)"> <g transform="translate(-50 -50)"> <g> <animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" values="0 50 50;360 50 50" keyTimes="0;1" dur="0.7575757575757576s"></animateTransform> <path fill-opacity="0.8" fill="#e15b64" d="M50 50L50 0A50 50 0 0 1 100 50Z"></path> </g> <g> <animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" values="0 50 50;360 50 50" keyTimes="0;1" dur="1.0101010101010102s"></animateTransform> <path fill-opacity="0.8" fill="#f47e60" d="M50 50L50 0A50 50 0 0 1 100 50Z" transform="rotate(90 50 50)"></path> </g> <g> <animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" values="0 50 50;360 50 50" keyTimes="0;1" dur="1.5151515151515151s"></animateTransform> <path fill-opacity="0.8" fill="#f8b26a" d="M50 50L50 0A50 50 0 0 1 100 50Z" transform="rotate(180 50 50)"></path> </g> <g> <animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" values="0 50 50;360 50 50" keyTimes="0;1" dur="3.0303030303030303s"></animateTransform> <path fill-opacity="0.8" fill="#abbd81" d="M50 50L50 0A50 50 0 0 1 100 50Z" transform="rotate(270 50 50)"></path> </g> </g> </g></g></div>`);
            $('#loader').css({
                "width": "100%",
                "height": "100%",
                "background-color": "rgba(204, 204, 204, 0.25)",
                "display":"block",
                "position":"absolute",
                "z-index":"9999",
                "top":"0px"
            });
            $('#loader svg').css({
                "top": "50%",
                "width": "5%",
                "text-align": "center",
                "left": "47%",
                "position": "fixed",
                "z-index":"9999"
            });
        }
    } else {
        $('#loader').remove();
    }
}
