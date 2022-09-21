$("document").ready(()=>{
    
form = document.getElementById("filter")
input = document.getElementById("search")

    input.addEventListener("keyup", ()=>{
        let data = input.value
        if(data.length > 2 || data.length == 1){
            //url
            let url = new URL(window.location.href)
            url = url.pathname + "?search="+data+"&ajax=1";
            console.log(url);
            $.ajax({
                type: "GET",
                url: url,
                success: function(data){
                    const content = document.querySelector("#content");
                    content.innerHTML = data.content
                },
            })
        }
    })
    form.addEventListener("submit", (e)=>{
        e.preventDefault();
    })

    let offset = 0;
    fetchMore = function(){
        if ($(window).scrollTop() > ($(document).height() - 200 )- $(window).height()){
            $(window).unbind('scroll', fetchMore);
            offset = offset + 10;
            let url = new URL(window.location.href)
            url = url.pathname + "?offset="+offset+"&ajax=1";
            console.log(url)
            $.ajax({
                type: "GET",
                url: url,
                success: function(data){
                    $(data.content).insertAfter($('#content'));
                },
            })
            setTimeout(()=> {
 $(window).bind('scroll',fetchMore);
            }, 3000)
        }
    } 
    $(window).bind('scroll',fetchMore);
})
