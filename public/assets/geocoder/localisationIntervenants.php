<!DOCTYPE html>
<title>Générateur</title>
<h1>
  <b>GÉNÉRATEUR D'ADRESSES</b>
<meta charset="utf-8" />
<script src="../assets/web/assets/jquery/jquery.min.js"></script><!--Importe jQuery-->
<a class="btn btn-secondary display-4 sideButton" style="float:right;position:absolute;right:10px" href="https://nominatim.openstreetmap.org/ui/search.html" target="_blank">Chercheur d'adresse</a>
   <form>
      <input type="button" value="Démarrer le générateur" onclick="myLoop();timeLoop();"><!--demarre la boucle + celle qui montre le temps restant-->
    </form>


	<div id="Time"></div>
    </h1>
    <div>
	  <div id="intervenant" style="overflow-y: auto;width: 30%;float: left;"></div>
      <div id="fichier" style="width: 50%;float: left;"></div>
	  <h1 style="text-align: center; width: 70%" id="nombre_Intervenants"></h1>
	</div>
<?php

require_once("../include/class.pdoBdChaudoudoux.inc.php");
$user='root';
$pdoChaudoudoux = new PdoBdChaudoudoux("localhost", "bdchaudoudoux", $user,"");

$lesChamps=$pdoChaudoudoux->obtenirListeChampI();
$quoi = " ";
foreach ($lesChamps as $unChamp) {
                        if (strpos($unChamp['COLUMN_NAME'], 'Candidats')){
                            $quoi .= "C.".$unChamp['COLUMN_NAME'];
                        } else if (strpos($unChamp['COLUMN_NAME'], 'Intervenants')){
                            $quoi .= "I.".$unChamp['COLUMN_NAME'];
                        } else {
                            $quoi .= $unChamp['COLUMN_NAME'];
                        }
                        $quoi .= " , ";

}
$quoi = substr($quoi, 0, -2);

$lesSalaries=$pdoChaudoudoux->obtenirListeSalarieSelect($quoi);
$counter = 0;
for ($i = 0; $i != count($lesSalaries); $i++) {
					    $counter += 1;
                        $place = $pdoChaudoudoux->PlaceOuNon($lesSalaries[$i][37]);
                        array_push($lesSalaries[$i], $place);

}
$archive=0;

$addr = array();
foreach($lesSalaries as $Salarie) {
	array_push($addr,	$Salarie[13].", ".$Salarie[14]." ".$Salarie[15]); // Tableau avec les adresses des intervenants 
}
?>

<script>

coord = [];                 //tableau avec toutes les coordonnees a la fin
intervenants = <?php echo json_encode($lesSalaries);?>;
addr = <?php echo json_encode($addr);?>;// tableau d'adresses
salarie_errone = "";
var i = 0;								//  set your counter to 1
j = ((intervenants.length - 1) * 5);	// 

var time = 0;//init du temps
function myLoop() {         //  create a loop function
  setTimeout(function() {   //  call a 5s setTimeout when the loop is called
    if(intervenants[0] != null){
      addr_search(addr[i],intervenants[i][0], i+1);  //  your code here
    }
    time = 5000;            // le temps que prends chaque boucle
    i++;                    //  increment the counter
    if (i < intervenants.length) {           //  if the counter < la longueur de famille, call the loop function
      myLoop();             //  ..  again which will trigger another 
    }                       //  ..  setTimeout()
  }, time)
}

var time2 = 0;

function timeLoop() {//de même que myLoop
  setTimeout(function() {
    var time = document.getElementById("Time");
    time.innerHTML = j + " seconde(s) restante(s)";//affiche un texte dans une balise html
    time2 = 1000;
    j--;          //diminue le temps restants
    if (j >= 0) {
      timeLoop();
    }
  }, time2)
}

function addr_search(addr,num_intervenant,i)
{
 var xmlhttp = new XMLHttpRequest();//demarre une instance XHR
 
 var url = "https://nominatim.openstreetmap.org/search?format=json&limit=3&q=" + addr;//construction de l'adresse où les données sont récupérées
 xmlhttp.onreadystatechange = function()
 {
   if (this.readyState == 4 && this.status == 200)//si le serveur est prêt et que l'on recoit les données, cette fonction est exécutée
   {
    var myArr = JSON.parse(this.responseText);//on récupère les données ici
    if(myArr[0] == null){//on teste si les données sont bonnes
      lat = 0;
      lon = i+1;
      salarie_errone = salarie_errone + num_intervenant + ',';//on ajoute l'intervenant au fichier txt avec les familles errone
    }
    else {lat = myArr[0].lat; lon = myArr[0].lon;}
    chooseAddr(lat, lon, num_intervenant, i, intervenants.length);
    if (i >= intervenants.length){
      downloadURI('data:text/html,' + JSON.stringify(coord), "intervenants.json");//permet de télécharger un fichier
      if (salarie_errone != ""){
        downloadURI('data:text/html,' + salarie_errone, "intervenants_errone.txt");
      }
	}
   }
 };
 xmlhttp.open("GET", url, true);
 xmlhttp.send();
}

var uneCoord = document.createElement("p");//crée un élément html
uneCoord.setAttribute("id", "counter");

function chooseAddr(lat, lon, num_intervenant, i, end)
{
 var intervenant = document.getElementById("intervenant");
 var nbrCoord = document.getElementById("counter");
 if (lat==0){
   var noCoord = document.createElement("p");
   noCoord.textContent = num_intervenant + ": Coordonnée Non Trouvée !";//si aucun résultat a été obtenu
   intervenant.appendChild(noCoord);//on rajoute l'élément crée au document
 }
 else{
   //uneCoord.textContent = num_intervenant + ": Coordonnée Trouvée !"
 }
 nbrCoord = i + " sur " + end;
 uneCoord.innerHTML = nbrCoord;//informe l'utilisateur sur combien de tours restants
 coord.push({"id" : num_intervenant, "latitude" : lat, "longitude" : lon});//ajouter au tableau "coord" les informations obtenues
 intervenant.appendChild(uneCoord);
}


function downloadURI(url, name) {
  var fichier = document.getElementById("fichier");
  var end = document.createElement("p");
  var link = document.createElement("a");
  end.textContent = " Le Générateur a fini, "+ name +" a été Téléchargé.";
  link.download = name;//nom et type du fichier
  link.href = url;//contenu du document
  link.innerHTML = "<button type='button'>Download</button>" ;
  fichier.appendChild(end);
  fichier.appendChild(link);
  /*link.click();//clique sur le lien
  fichier.removeChild(link);
  delete link;*/
}
</script>