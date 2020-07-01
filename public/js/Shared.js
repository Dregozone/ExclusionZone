function enlargePDA(type) {

    console.log(type);

    let pda = document.getElementById("PDA");
    let pdaBlocker = document.getElementById("pdaBlocker");
    let showHide = document.getElementById("showHidePDA");

    // Set PDA contents
    ////

    // Show PDA Blocker
    pdaBlocker.style.visibility = "visible";
    showHide.style.visibility = "hidden";

    // Show PDA
    pda.style.bottom = "5%";
    pda.style.right = "20%";
    pda.style.width = "60%";
    pda.style.height = "90%";
}

function shrinkPDA() {

    let pda = document.getElementById("PDA");
    let pdaBlocker = document.getElementById("pdaBlocker");
    let showHide = document.getElementById("showHidePDA");

    // Hide PDA Blocker
    pdaBlocker.style.visibility = "hidden";
    showHide.style.visibility = "visible";

    // Hide PDA
    pda.style.bottom = "1vmin";
    pda.style.right = "1vmin";
    pda.style.width = "28vmin";
    pda.style.height = "45vmin";
}

function hidePDA() {

    let pda = document.getElementById("PDA");
    let showHide = document.getElementById("showHidePDA");

    pda.style.bottom = "-43vmin";
    showHide.innerHTML = "Show PDA";
    showHide.onclick = showPDA;
}

function showPDA() {

    let pda = document.getElementById("PDA");
    let showHide = document.getElementById("showHidePDA");

    pda.style.bottom = "1vmin";
    showHide.innerHTML = "Hide PDA";
    showHide.onclick = hidePDA;
}
