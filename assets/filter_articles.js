$("document").ready(()=>{

    let selectInput = document.getElementById("filter-users");
    let form = document.getElementById("form-select");
    let selectInputs = document.querySelectorAll("#form-select select");
    let searchInput = document.getElementById("search")
    
    function ajaxToFilter(){
        const formData = new FormData(form)
        //queryString
        const param = new URLSearchParams();
        formData.forEach((value, key) => {
            param.append(key, value)
        })
        let url = new URL(window.location.origin)
        url = url + "request-ajax-list-all-articles?"+param.toString();
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
    selectInputs.forEach(input => { 
        input.addEventListener("change", ()=>{
            ajaxToFilter();
        })
    });
    searchInput.addEventListener("keyup", ()=>{
            ajaxToFilter();
        })

        form.addEventListener("submit", (e)=>{
            e.preventDefault();
        })

    })

    