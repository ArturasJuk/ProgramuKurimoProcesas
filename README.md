# Dead link finder

## Naudojimas:
1. atsidaryti projekto direktorijoje terminala
2. programa paleidziama terminale rasant: <br/>
`php finder.php url`<br/>
vietoje url irasomas puslapio url, pvz: `php finder.php https://www.google.com`<br/>
nenurodzius url bus tikrinamas "https://dead-links.freesite.host/" puslapis
3. pradejus tikrinima parasomas pranesimas, pvz: `Checking for deadlinks in "https://www.google.com"`
4. programos veikimo metu terminale isvedami tikrinami url
5. baigus tikrinima parasomas baigimo pranesimas, pvz: `Check completed, results saved in "output.csv"`
* jei buvo rasta mirusiu link'u:
    * rezultatai issaugomi `output.csv` faile<br/>
    * jei toks failas jau yra - jis perrasomas
    * jei failas atidarytas - bus isvestas klaidos pranesimas:<br/>
    `ERROR: Writting results to file "output.csv" failed`
* jei nerasta mirusiu link'u:
    * terminale isvedamas pranesimas:<br/>
    `Check completed, no deadlinks were found.`

terminalas = console/terminal<br/>
csv failo celiu atskyrimo simbolis yra kablelis(`,`) ji galima pakeisti programos kode nurodant norima simboli, kai kvieciama GetCSV funkcija: `GetCSV($deadLinkai, ";");`<br/>
trumpi video, kaip naudotis:
* Rasta mirusiu linku:
[![Rasta mirusiu linku](https://raw.githubusercontent.com/ArturasJuk/ProgramuKurimoProcesas/master/gif/deadFound.gif)](https://raw.githubusercontent.com/ArturasJuk/ProgramuKurimoProcesas/master/gif/deadFound.gif)

* Nerasta mirusiu linku:
[![Nerasta mirusiu linku](https://raw.githubusercontent.com/ArturasJuk/ProgramuKurimoProcesas/master/gif/deadNotFound.gif)](https://raw.githubusercontent.com/ArturasJuk/ProgramuKurimoProcesas/master/gif/deadFound.gif)