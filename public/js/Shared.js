var pdaStatus = "show";
var backpackStatus = "show";
var wearingStatus = "show";
var statsStatus = "show";

var shrinkIcon = "<";
var growIcon = ">";

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
    pda.style.bottom = "5vmin";
    pda.style.right = "22vmin";
    pda.style.width = "56vmin";
    pda.style.height = "90vmin";

    pdaStatus = "enlarged";
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

    pdaStatus = "shrunk";
}

function hidePDA() {

    let pda = document.getElementById("PDA");
    let showHide = document.getElementById("showHidePDA");

    pda.style.bottom = "-43vmin";
    showHide.innerHTML = "Show PDA";
    showHide.onclick = showPDA;

    pdaStatus = "hidden";
}

function showPDA() {

    let pda = document.getElementById("PDA");
    let showHide = document.getElementById("showHidePDA");

    pda.style.bottom = "1vmin";
    showHide.innerHTML = "Hide PDA";
    showHide.onclick = hidePDA;

    pdaStatus = "show";
}

function hideBackpack() {

    let backpack = document.getElementById("backpack");
    let backpackSlider = document.getElementById("backpackSlider");
    let backpackContent = document.getElementById("backpackContent");

    let stats = document.getElementById("stats");
    let wearing = document.getElementById("wearing");

    backpack.style.left = "-24vmin";
    backpackSlider.innerHTML = growIcon;
    backpackSlider.onclick = showBackpack;
    backpackContent.style.visibility = "hidden";

    stats.style.left = "1vmin";
    wearing.style.left = "1vmin";

    backpackStatus = "hidden";
}

function showBackpack() {

    let backpack = document.getElementById("backpack");
    let backpackSlider = document.getElementById("backpackSlider");
    let backpackContent = document.getElementById("backpackContent");

    let stats = document.getElementById("stats");
    let wearing = document.getElementById("wearing");

    backpack.style.left = "0";
    backpackSlider.innerHTML = shrinkIcon;
    backpackSlider.onclick = hideBackpack;
    backpackContent.style.visibility = "visible";

    stats.style.left = "25vmin";
    wearing.style.left = "25vmin";

    backpackStatus = "show";
}

function hideWearing() {

    let wearing = document.getElementById("wearing");
    let wearingSlider = document.getElementById("wearingSlider");
    let wearingContent = document.getElementById("wearingContent");

    wearing.style.width = "1vmin";
    wearingSlider.innerHTML = growIcon;
    wearingSlider.onclick = showWearing;
    wearingContent.style.visibility = "hidden";

    wearingStatus = "hidden";
}

function showWearing() {

    let wearing = document.getElementById("wearing");
    let wearingSlider = document.getElementById("wearingSlider");
    let wearingContent = document.getElementById("wearingContent");

    wearing.style.width = "70vmin";
    wearingSlider.innerHTML = shrinkIcon;
    wearingSlider.onclick = hideWearing;
    wearingContent.style.visibility = "visible";

    wearingStatus = "show";
}

function hideStats() {

    let stats = document.getElementById("stats");
    let statsSlider = document.getElementById("statsSlider");
    let statsContent = document.getElementById("statsContent");

    stats.style.width = "1vmin";
    statsSlider.innerHTML = growIcon;
    statsSlider.onclick = showStats;
    statsContent.style.visibility = "hidden";

    statsStatus = "hidden";
}

function showStats() {

    let stats = document.getElementById("stats");
    let statsSlider = document.getElementById("statsSlider");
    let statsContent = document.getElementById("statsContent");

    stats.style.width = "17vmin";
    statsSlider.innerHTML = shrinkIcon;
    statsSlider.onclick = hideStats;
    statsContent.style.visibility = "visible";

    statsStatus = "show";
}
