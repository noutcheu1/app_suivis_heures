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
      <div id="famille" style="overflow-y: auto;width: 50%;float: left;"></div>
      <div id="fichier" style="width: 50%;float: left;"></div>
    </div>
<?php

require_once("pdo.php");
$user='root';
$pdoChaudoudoux = new PdoBdChaudoudouxExtra("localhost", "bdchaudoudoux", $user,"");

$lesFamilles = $pdoChaudoudoux->obtenirListeFamilleAPourvoir();

$adresse = array();
foreach($lesFamilles as $uneFamille) {
    $num = $uneFamille["numero_Famille"];
    $coord = $pdoChaudoudoux->obtenirDetailFamille($num);
    array_push($adresse,$coord["adresse_Famille"].", ".$coord["cp_Famille"]." ".$coord["ville_Famille"]);//J'obtiens un tableau avec toutes les adresses des familles
} ?>
<script>
coord = [];                 //tableau avec toutes les coordonnees a la fin
famille = <?php echo json_encode($lesFamilles);?>;//tableau avec le numéro des familles
addr = <?php echo json_encode($adresse);?>;//tableau avec les adresses
famille_errone = "";
var i = 0;                     //  set your counter to 1
j = ((famille.length - 1) * 5);// 


$(document).ready(function() {//démarrage de jQuery
  var myItems;
  var Item;
  var length = famille.length;//pour que la durée de la boucle ne diminue pas
  $.getJSON('../assets/json/familles.json', function(data) {//recupere les données du json
      myItems = data;
      for (let m = length; m > 0; m--){
        if(myItems.find(element => element["id"] == famille[m-1].numero_Famille)){//si on trouve un élément identique dans le fichier json que dans le base
          Item = myItems.findIndex(element => element["id"] == famille[m-1].numero_Famille);//on l'assigne à une var temp
          myItems.splice(Item,1);     //on l'enleve de myItems aussi
        }
      }
      for (let index = 0; index < myItems.length; index++) {//ce qui permet de rajouter tout ceux qui n'ont pas dans les familles a pourvoir        console.log(myItems[index]);
        coord.push(myItems[index]);                         //Pour une utilisation ultérieur
      }
      if(famille.length == 0){j = 0;}//si toutes les familles sont deja dans le fichier json
      else{
        j = ((famille.length - 1) * 5);//Boucle pour le temps
      }
  })
});





var time = 0;//init du temps

function myLoop() {         //  create a loop function
  setTimeout(function() {   //  call a 5s setTimeout when the loop is called
    if(famille[0] != null){
      addr_search(addr[i],famille[i].numero_Famille, i+1);  //  your code here
    }
    else{//si toutes les familles sont deja dans le fichier json
      var end = document.createElement("p");
      end.innerHTML = "familles.json contient toutes les familles à pourvoir que la base";
      document.body.appendChild(end);
    }
    time = 5000;            // le temps que prends chaque boucle
    i++;                    //  increment the counter
    if (i < famille.length) {           //  if the counter < la longueur de famille, call the loop function
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

function addr_search(addr,num_famille,i)
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
      famille_errone = famille_errone + num_famille + ',';//on ajoute la famille au fichier txt avec les familles errone
    }
    else {lat = myArr[0].lat; lon = myArr[0].lon;}
    chooseAddr(lat, lon, num_famille, i, famille.length);
    if (i >= famille.length){
      downloadURI('data:text/html,' + JSON.stringify(coord), "famille.json");//permet de télécharger un fichier
      if (famille_errone != ""){
        downloadURI('data:text/html,' + famille_errone, "famille_errone.txt");
      }
    }
   }
 };
 xmlhttp.open("GET", url, true);
 xmlhttp.send();
}

function chooseAddr(lat, lon, num_famille, i, end)
{
 var famille = document.getElementById("famille");
 var uneCoord = document.createElement("p");//crée un élément html
 if (lat==0){
  uneCoord.textContent = num_famille + ": Coordonnée Non Trouvée !";//si aucun résultat a été obtenu
 }
 else{
   uneCoord.textContent = num_famille + ": Coordonnée Trouvée !"
 }
 uneCoord.innerHTML = uneCoord.textContent + "<br/>" + i + " sur " + end;//informe l'utilisateur sur combien de tours restants
 coord.push({"id" : num_famille, "latitude" : lat, "longitude" : lon});//ajouter au tableau "coord" les informations obtenues
 famille.appendChild(uneCoord);//on rajoute l'élément crée au document
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

console.log(coord);
</script>