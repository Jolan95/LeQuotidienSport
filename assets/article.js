
postId = $('#data').data('post')
$("#my-rating").starRating({
    starSize: 30,
    ratedColor : "gold",
    activeColor : "gold",
    hoverColor : "gold",
    ratedColors : ["gold", "gold", "gold", "gold", "gold"],
    callback: function(currentRating, $el){
        let url = new URL(window.location.origin)
        url = url + "rating-article?rate="+currentRating+"?&post="+postId;
        $.ajax({
            type: "GET",
            url: url,
            success: function(data){
                $("#my-rating").html("Merci pour votre vote !");
            },
        })
    }
});