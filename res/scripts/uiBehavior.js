function hideUpdateMenu(){	
/// Used to hide the update warning on click. Currently unused.
	document.getElementById('updateMenu').style.display = "none";
}

function toggleSettingsMenu (){	
/// Opens or closes settings window
	let setMenu = document.getElementById('settingsMenu');
	if (setMenu.style.display == "block"){
		setMenu.style.display="none";
	}
	else{
		setMenu.style.display ="block";
	}
}

function openAllBlueprintsSections(id){	
/// Deploy build lists in the comparator, for factories / fatboys, etc...
	let bpList = document.getElementsByClassName('unitBlueprints');
	let mode = false;
	if (document.getElementById(id).open){
		mode = true;
	}
	for (i = 0; i < bpList.length; i++) {
		if (bpList[i].id != id){
			if (mode){
				bpList[i].removeAttribute('open');
			}
			else{
				bpList[i].setAttribute('open', 'open');
			}
		}
	}
}

function removeUnitFromComparator(id){
/// Kicks an unit out of comparator and refreshes the page.
console.log( getUrlVars().get("id"));
	const unitList = getUrlVars().get("id");
	let unitArr = unitList.split(',');
	const index = unitArr.indexOf(id);
	if (index > -1){
		unitArr.splice(index, 1);
	}
	if (unitArr.length < 1){
		window.location.search = "";
	}
	else{
		seeUnit(unitArr.join(','));
	}
}
function getUrlVars() {
/// Gets the GET variables from the URL
	return (new URL(document.location).searchParams);
}
function seeUnit(id=null){ 
/// Used to enter the comparator on click, in list mode
	const checkList = document.getElementsByClassName('unitMainDiv');
	let idList = [];
	for (i = 0; i < checkList.length; i++) {
		if (checkList[i].classList.contains('unitSelected')){
			idList.push(checkList[i].id);
		}
	}
	if (id != null){
		idList.push(id);
	}
	id = idList.join();
	if (id == "" || id == null){
		window.location.search="";
	}
	else{
		window.location.search = "id="+id;
	}
}
function toggleSelect(div_id){
/// Select or deselect an unit on click, in the list mode
	let line = document.getElementById(div_id);
	if (line.classList.contains('unitSelected')){
		line.classList.remove('unitSelected');
	}
	else{
		line.classList.add('unitSelected');
	}
	checkForUnitsToCompare();
}
function checkForUnitsToCompare(){
/// Check if multiple units are selected or not so it can displays the "Open comparator" button
	let selected = document.getElementsByClassName('unitMainDiv');
	let amount = 0;
	for (i = 0; i < selected.length; i++) {
		if (selected[i].classList.contains('unitSelected')){
			amount++;
		}
	}
	if (document.getElementById("comparatorPopup") != undefined){
		if (amount > 1){
			document.getElementById("comparatorPopup").removeAttribute('hidden');
		}
		else{
			document.getElementById("comparatorPopup").setAttribute('hidden', 'hidden');
		}
	}
}

function openTab(class_button, id_button, class_tabClass, id_tabToOpen) { 
/// Used to open or close all weapon tabs in the comparator at the same time
	let i;
	let x = document.getElementsByClassName(class_tabClass);
	for (i = 0; i < x.length; i++) {
		x[i].style.display = "none"; 
	}
	document.getElementById(id_tabToOpen).style.display = "block"; 
	
	let tabs = document.getElementsByClassName(class_button);
	for (i = 0; i < tabs.length; i++) {
		tabs[i].removeAttribute("selected");
	}
	document.getElementById(id_button).setAttribute("selected", "selected");
}

function loadHoverInfo(str_unitId, str_lang){
/// Displays the unit title in a corner of the screen

	if ( document.getElementById('tooltipZone') == undefined){
		return;
	}
	
	let http = new XMLHttpRequest();
	
	http.onload = function() {
		console.log("Received");
		document.getElementById('tooltipZone').innerHTML = http.responseText;
	}
	
	http.open("GET", "res/scripts/unitTitle.php?id="+str_unitId+"&localization="+str_lang+"", true);
	http.send();
	
}