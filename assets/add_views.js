$("document").ready(()=>{
    setTimeout(() => {
       let element = document.getElementById("data-article")
       let data = element.dataset.article
       let url = new URL(window.location.origin)
       url = url + "article/add-view?article="+data;
       $.ajax({
        type: "GET",
        url: url,
        success: function(data){

        },
    })
    }, 15000);
})    