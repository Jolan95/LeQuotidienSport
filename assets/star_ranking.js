
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


$('#comment_content').bind('keyup', function(e){

    $("#count-chars").text($(this).val().length);
    if($(this).val().length > 389){
        console.log("less")
        $("#count-chars").addClass( "red" );
    }else{
        console.log("more")
        $("#count-chars").removeClass("red");
    }    
});