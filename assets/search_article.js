$("document").ready(()=>{
    
    form = document.getElementById("filter")
    input = document.getElementById("search")
        
        input.addEventListener("keyup", ()=>{
            let data = input.value
            console.log(data)
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

        })
        
        form.addEventListener("submit", (e)=>{
            e.preventDefault();
        })
    })
