$("document").ready(()=>{
    
    let selectInput = document.getElementById("tri-select");
    let form = document.getElementById("form-select");
    selectInput.addEventListener("change", ()=>{

            let data = selectInput.value
            let url = new URL(window.location.origin)
            url = url + "order-article?value="+data;
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