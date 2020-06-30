function enlargePDA(type) {

    console.log(type);

    let pda = document.getElementById("PDA");
    let pdaBlocker = document.getElementById("pdaBlocker");

    // Set PDA contents
    ////

    // Show PDA Blocker
    pdaBlocker.style.visibility = "visible";

    // Show PDA
    pda.style.bottom = "5%";
    pda.style.right = "20%";
    pda.style.width = "60%";
    pda.style.height = "90%";
}

function shrinkPDA() {

    let pda = document.getElementById("PDA");
    let pdaBlocker = document.getElementById("pdaBlocker");

    // Hide PDA Blocker
    pdaBlocker.style.visibility = "hidden";

    // Hide PDA
    pda.style.bottom = "1vmin";
    pda.style.right = "1vmin";
    pda.style.width = "28vmin";
    pda.style.height = "45vmin";
}
