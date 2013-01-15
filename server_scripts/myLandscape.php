<?php
header('Content-type: text/html; utf-8');

require_once("config.php");
?> 
<!DOCTYPE html>
<html lang="en">
	<head>
		<title>three.js webgl - trackball camera</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
		<style>
			body {
				color: #000;
				font-family:Monospace;
				font-size:13px;
				text-align:center;
				font-weight: bold;

				background-color: #fff;
				margin: 0px;
				overflow: hidden;
			}
		</style>
	</head>

	<body>
        <div id="cont" ></div>

		<div id="container"></div>

		<script src="js/three.js"></script>

		<script src="js/controls/TrackballControls.js"></script>

		<script src="js/Detector.js"></script>
		<script src="js/libs/stats.min.js"></script>
		
		<script type="text/javascript" src="../scripts/XMLHttpRequest.js"></script>
        <script type="text/javascript" src="../scripts/Functions.js"></script> 

		<script>
//Класс тайл		
function Tile () {
    this.id;
	this.lvl;//уровень
	this.center;//THREE.Vector3
    this.childs = new Array();//4 id потомков 
    this.childs[0];
    this.childs[1];
    this.childs[2];
	this.childs[3];
    this.prnt;//предок
	this.triangleGeometry = new THREE.Geometry();
	this.triangleGeometry.faces.push(new THREE.Face3(0,1,3));
	this.triangleGeometry.faces.push(new THREE.Face3(1,2,3));
	this.triangleGeometry.faceVertexUvs[0].push( [
            new THREE.UV( 0, 1 ),
            new THREE.UV( 1, 1 ),
			new THREE.UV( 0, 0 )
        ] );
	this.triangleGeometry.faceVertexUvs[0].push( [
            new THREE.UV( 1, 1 ),
            new THREE.UV( 1, 0 ),
            new THREE.UV( 0, 0 )
        ] );
	function ver_dist(cam_x,cav_y,cam_z) {

    }
}

			if ( ! Detector.webgl ) Detector.addGetWebGLMessage();

			var container, stats;
			
			var tiles = new Array();
			

<?php
//выбираем с базы 3 уровня детализации и заполняем тайл
$query= <<<EOD
SELECT tile.id,tile.lvl,ar_verts.verts,tile.id_t_c1,tile.id_t_c2,tile.id_t_c3,tile.id_t_c4,
tile.id_t_p FROM ar_verts,tile WHERE tile.id>=0 and tile.id<=20 and ar_verts.id=tile.id_av
EOD;
$usr=mysql_query($query);
if(!$usr)exit("Ошибка - ".mysql_error());
while($row = mysql_fetch_array($usr)){ 

$verts=explode(" ",trim($row['verts']));
$id=$row['id'];
$lvl=$row['lvl'];
$id_t_c1=$row['id_t_c1'];
$id_t_c2=$row['id_t_c2'];
$id_t_c3=$row['id_t_c3'];
$id_t_c4=$row['id_t_c4'];
$id_t_p=$row['id_t_p'];
$json_data = array ('id'=>$id,'verts'=> $verts);
$json_str = json_encode($json_data);
//<script language='javascript'>
$js= <<<EOL
tile = new Tile ();
tile.id=$id;
tile.lvl=$lvl;
tile.childs[0]=$id_t_c1;
tile.childs[1]=$id_t_c2;
tile.childs[2]=$id_t_c3;
tile.childs[3]=$id_t_c4;
tile.prnt=$id_t_p;
var jstr=JSON.parse('$json_str');
var arr_verts = jstr.verts;
        for(i=0;i<arr_verts.length;i+=3){
		tile.triangleGeometry.vertices.push(new THREE.Vector3( parseFloat(arr_verts[i]), parseFloat(arr_verts[i+1]),parseFloat(arr_verts[i+2])));
				                        };
vec1=tile.triangleGeometry.vertices[0];
vec2=tile.triangleGeometry.vertices[1];
vec3=tile.triangleGeometry.vertices[2];
vec4=tile.triangleGeometry.vertices[3];
min1=Math.min(vec4.y,vec3.y);min2=Math.min(vec2.y,vec1.y);min=Math.min(min1,min2);
max1=Math.max(vec4.y,vec3.y);max2=Math.max(vec2.y,vec1.y);max=Math.max(max1,max2);
ceny=(min+max)/2.0;
cenx=(vec2.x+vec1.x)/2.0;
cenz=(vec2.z+vec3.z)/2.0;
tile.center=new THREE.Vector3(cenx,ceny,cenz);
tiles[$id]=tile;
EOL;
print $js;
}	

?>

			var camera, controls, scene, renderer;
			
			var texture;

			var cross;
			
			var triangleGeometry;
			
			var triangleMesh = new Array();
			var triangleMaterial;

            var div = document.getElementById('cont');
			div.ongetdata =update_data;	
			init();
			animate();


        

			function init() {
			ch=0;
			    zoom=6;
				lon=11.76;
				lat=41.97;
			    x_t=lon2tile(lon,zoom);
				y_t=lat2tile(lat,zoom);
			    var texture=THREE.ImageUtils.loadTexture('http://c.tile.openstreetmap.org/'+zoom+'/'+x_t+'/'+y_t+'.png');
                
				camera = new THREE.PerspectiveCamera( 45, window.innerWidth / window.innerHeight, 1, 100 );
				camera.position.set(0, 0, 14.0);

				controls = new THREE.TrackballControls( camera );

				controls.rotateSpeed = 1.0;
				controls.zoomSpeed = 1.2;
				controls.panSpeed = 0.8;

				controls.noZoom = false;
				controls.noPan = false;

				controls.staticMoving = true;
				controls.dynamicDampingFactor = 0.3;

				controls.keys = [ 65, 83, 68 ];

				controls.addEventListener( 'change', render );

                triangleMaterial = new THREE.MeshBasicMaterial({
					//vertexColors:THREE.VertexColors,
					    //'map': texture,
						side:THREE.DoubleSide,
                        'overdraw': true
				});

				//scene
                scene = new THREE.Scene();
				scene.fog = new THREE.FogExp2( 0xcccccc, 0.002 );

				//var material =  new THREE.MeshLambertMaterial( { color:0xffffff, shading: THREE.FlatShading } );



				/*triangleGeometry.faces[0].vertexColors[0] = new THREE.Color(0xFF0000);
				triangleGeometry.faces[0].vertexColors[1] = new THREE.Color(0x00FF00);
				triangleGeometry.faces[0].vertexColors[2] = new THREE.Color(0x0000FF);*/

				// Create a mesh and insert the geometry and the material. Translate the whole mesh
				// by -1.5 on the x axis and by 4 on the z axis. Finally add the mesh to the scene.
				/*triangleMesh[0] = new THREE.Mesh(tiles[0].triangleGeometry, triangleMaterial);
				triangleMesh[0].position.set(0.0, 0.0, 0.0);
				scene.add(triangleMesh[0]);*/
				
				//заполняем меши наших тайлов и добавляем в сцену
		for ( i = 0; i <tiles.length;i++) {
                 	  triangleMesh[i] = new THREE.Mesh(tiles[i].triangleGeometry, triangleMaterial);
				      triangleMesh[i].position.set(0.0, 0.0, 0.0);
					  scene.add(triangleMesh[i]);
					  triangleMesh[i].visible=false;
			}
				triangleMesh[0].visible=true;

				triangleMaterial.wireframe=true;
				//triangleMaterial.color=new THREE.Color(0x2ed149);
				
				// lights

				light = new THREE.DirectionalLight( 0xffffff );
				light.position.set( 1, 1, 1 );
				scene.add( light );

				light = new THREE.DirectionalLight( 0x002288 );
				light.position.set( -1, -1, -1 );
				scene.add( light );

				light = new THREE.AmbientLight( 0x222222 );
				scene.add( light );	
				
				// renderer

				renderer = new THREE.WebGLRenderer( { antialias: false } );
				//renderer.setClearColor( scene.fog.color, 1 );
				renderer.setClearColor( new THREE.Color(0x1a3516), 1 );
				renderer.setSize( window.innerWidth, window.innerHeight );

				container = document.getElementById( 'container' );
				container.appendChild( renderer.domElement );

				stats = new Stats();
				stats.domElement.style.position = 'absolute';
				stats.domElement.style.top = '0px';
				stats.domElement.style.zIndex = 100;
				container.appendChild( stats.domElement );

				//

				window.addEventListener( 'resize', onWindowResize, false );

               //wrt("clear")
			  /* for(var i = 0; i < tiles[0].triangleGeometry.vertices.length; i++) {
                         wrt(""+i+" "+tiles[0].triangleGeometry.vertices[i].x+" "+tiles[0].triangleGeometry.vertices[i].y+" "+tiles[0].triangleGeometry.vertices[i].z)
	                  }
					  wrt("center "+tiles[0].center.x+" "+tiles[0].center.y+" "+tiles[0].center.z)*/
			   /*console.debug("tiles[0].center.x "+tiles[0].center.x)
			   console.debug("tiles[1].center.x "+tiles[1].center.x)
			   console.debug("tiles[2].center.x "+tiles[2].center.x)
			   console.debug("tiles[3].center.x "+tiles[3].center.x)
			   console.debug("tiles[4].center.x "+tiles[4].center.x)*/
			   
			   //setTimeout(verify, 250)
			   
			   //land_func(53)
			}
			
			//функции вызываюшая при ответе с сервера на запрос получения тайла по айди
			function update_data(s) {
			//var div = document.getElementById('cont');
            //alert(s);
			    //str_vert=s.trim();//!!! убери в  генераторе в конце пробел
			    //var arr_verts = str_vert.split(/\s+/);
                jstr=JSON.parse(''+s);		
                tile = new Tile ();
                tile.id=jstr.id;
                tile.lvl=jstr.lvl;
                tile.childs[0]=jstr.id_t_c1;
                tile.childs[1]=jstr.id_t_c2;
                tile.childs[2]=jstr.id_t_c3;
                tile.childs[3]=jstr.id_t_c4;
                tile.prnt=jstr.id_t_p;

                var arr_verts = jstr.verts;
                    for(i=0;i<arr_verts.length;i+=3){
		               tile.triangleGeometry.vertices.push(new THREE.Vector3( parseFloat(arr_verts[i]), parseFloat(arr_verts[i+1]),parseFloat(arr_verts[i+2])));
				                            };
                vec1=tile.triangleGeometry.vertices[0];
                vec2=tile.triangleGeometry.vertices[1];
                vec3=tile.triangleGeometry.vertices[2];
                vec4=tile.triangleGeometry.vertices[3];
                min1=Math.min(vec4.y,vec3.y);min2=Math.min(vec2.y,vec1.y);min=Math.min(min1,min2);
                max1=Math.max(vec4.y,vec3.y);max2=Math.max(vec2.y,vec1.y);max=Math.max(max1,max2);
                ceny=(min+max)/2.0;
                cenx=(vec2.x+vec1.x)/2.0;
                cenz=(vec2.z+vec3.z)/2.0;
                tile.center=new THREE.Vector3(cenx,ceny,cenz);//alert(""+tile.center.x+""+tile.center.y+""+tile.center.z)
                tiles[jstr.id]=tile;

                 	  triangleMesh[jstr.id] = new THREE.Mesh(tiles[jstr.id].triangleGeometry, triangleMaterial);
				      triangleMesh[jstr.id].position.set(0.0, 0.0, 0.0);
					  scene.add(triangleMesh[jstr.id]);
					  triangleMesh[jstr.id].visible=false;

				                      }

			function onWindowResize() {

				camera.aspect = window.innerWidth / window.innerHeight;
				camera.updateProjectionMatrix();

				renderer.setSize( window.innerWidth, window.innerHeight );

				controls.handleResize();

				render();

			}

			function animate() {

				requestAnimationFrame( animate );
				controls.update();

			}
			
			function verify(){
				/*console.debug(" ")	
				console.debug("cur_t_ids.length "+cur_t_ids.length)				    
				for(i=0;i<cur_t_ids.length;i++){
				console.debug("cur_t_ids[i] "+cur_t_ids[i])
				dyn_lvl(camera.position,tiles[cur_t_ids[i]].id);					
				                               }
			    setTimeout(verify, 250)*/
			}

			function render() {
				//wrt("cur_t_ids.length "+cur_t_ids.length+" ")//div.innerHTML+="cur_t_ids.length "+cur_t_ids.length+" "
				console.debug(" ")	
				console.debug("tiles.length "+tiles.length)					
				for(i=0;i<tiles.length;i++){
				    console.debug("i "+i)
					if(tiles[i]!=null&&triangleMesh[i].visible){console.debug("tiles[i].id "+tiles[i].id);dyn_lvl(camera.position,tiles[i].id);}			
				                }								

				renderer.render( scene, camera );
				stats.update();

			}
			
			function getDistance(cam,id){
			    console.debug("id "+id)
				ax=Math.max(cam.x,tiles[id].center.x)-Math.min(cam.x,tiles[id].center.x);
				//console.debug("cam.x "+cam.x+"tiles["+(id)+"].center.x "+tiles[id].center.x)
				//console.debug("ax "+ax)
				//ay=Math.max(cam.y,tiles[id].center.y)-Math.min(cam.y,tiles[id].center.y);
				az=Math.max(cam.z,tiles[id].center.z)-Math.min(cam.z,tiles[id].center.z);
				cD=Math.sqrt(ax*ax/*+ay*ay*/+az*az);
				//cD=1 * cD.toFixed(1)
				console.debug("cR "+cD+"id "+id)
                return cD				
			}
			
			function dyn_lvl(cam,id){

			  flagDrop=false;
			  chldsExist=true;
              if(tiles[id].childs[0]>=0&&tiles[tiles[id].childs[0]]==null){land_func(tiles[id].childs[0]);chldsExist=false;}
              if(tiles[id].childs[1]>=0&&tiles[tiles[id].childs[1]]==null){land_func(tiles[id].childs[1]);chldsExist=false;}
			  if(tiles[id].childs[2]>=0&&tiles[tiles[id].childs[2]]==null){land_func(tiles[id].childs[2]);chldsExist=false;}
			  if(tiles[id].childs[3]>=0&&tiles[tiles[id].childs[3]]==null){land_func(tiles[id].childs[3]);chldsExist=false;}
						
			  if(tiles[id].childs[0]>=0&&chldsExist){	 

			  //константное растояние по уровню текущего тайла
			  activatedDistChild=10.0-((tiles[id].lvl+1)*1.5)
			  //console.debug("activatedDistChild "+activatedDistChild)

			    ch_id1=tiles[id].childs[0];
				ch_id2=tiles[id].childs[1];
				ch_id3=tiles[id].childs[2];
				ch_id4=tiles[id].childs[3];
				console.debug("id "+id)
                //хотя бы для одного чайлда растояние меньше				
			    if(getDistance(cam,ch_id1)<activatedDistChild||getDistance(cam,ch_id2)<activatedDistChild||getDistance(cam,ch_id3)<activatedDistChild||getDistance(cam,ch_id4)<activatedDistChild){flagDrop=true;}				
				//опускаемя на уровень ниже(делим квад на 4)
				   if(flagDrop){
				    
				    //wrt("del "+id)
                    //scene.remove(triangleMesh[id]);
					triangleMesh[id].visible=false;	
					//renderer.deallocateObject(triangleMesh[id]);
					//del_indx=cur_t_ids.indexOf(id);//console.debug("del_indx "+del_indx)
					/*if(del_indx>=0)cur_t_ids.splice(del_indx,1);
                     cur_t_ids.push(ch_id1);
					 cur_t_ids.push(ch_id2);
					 cur_t_ids.push(ch_id3);
					 cur_t_ids.push(ch_id4); */
					 //cur_t_ids.splice(del_indx,1,ch_id1,ch_id2,ch_id3,ch_id4);
					//wrt("add "+(4*id+1))
                    /*triangleMesh[4*id+1] = new THREE.Mesh(tiles[4*id+1].triangleGeometry, triangleMaterial);
				    triangleMesh[4*id+1].position.set(0.0, 0.0, 0.0);
				    scene.add(triangleMesh[4*id+1]);*/
					
                    triangleMesh[ch_id1].visible=true;						
				    //cur_t_ids[del_indx]=(4*id+1);//
					//cur_t_ids.push(4*id+1);
					
					//wrt("add "+(4*id+2))
					/*triangleMesh[4*id+2] = new THREE.Mesh(tiles[4*id+2].triangleGeometry, triangleMaterial);
				    triangleMesh[4*id+2].position.set(0.0, 0.0, 0.0);
				    scene.add(triangleMesh[4*id+2]);*/
                    triangleMesh[ch_id2].visible=true;						
				    //cur_t_ids.push(4*id+2);
					
					//wrt("add "+(4*id+3))
					/*triangleMesh[4*id+3] = new THREE.Mesh(tiles[4*id+3].triangleGeometry, triangleMaterial);
				    triangleMesh[4*id+3].position.set(0.0, 0.0, 0.0);
				    scene.add(triangleMesh[4*id+3]);*/
                    triangleMesh[ch_id3].visible=true;					
				    //cur_t_ids.push(4*id+3);
					
					//wrt("add "+(4*id+4))
					/*triangleMesh[4*id+4] = new THREE.Mesh(tiles[4*id+4].triangleGeometry, triangleMaterial);
				    triangleMesh[4*id+4].position.set(0.0, 0.0, 0.0);
				    scene.add(triangleMesh[4*id+4]);*/
                    triangleMesh[ch_id4].visible=true;					
				    //cur_t_ids.push(4*id+4);
                                }
				 
				 
				            }
				 
				 flagRise=false;
				 //имеет ли тайл предка
				 if(tiles[id].prnt>=0){
				    prntId=tiles[id].prnt;
				     ch_id1=tiles[prntId].childs[0];
					 ch_id2=tiles[prntId].childs[1];
					 ch_id3=tiles[prntId].childs[2];
					 ch_id4=tiles[prntId].childs[3];
					 //console.debug("prntId "+prntId+"ch_id4 "+cur_t_ids.indexOf(ch_id4)+""+" ch_id4 "+cur_t_ids.indexOf(ch_id4)+""+"ch_id4 "+cur_t_ids.indexOf(ch_id4)+""+" ch_id1 "+ch_id1+"indx "+cur_t_ids.indexOf(ch_id1))
				    //console.debug("cur_t_ids.indexOf(ch_id4) "+cur_t_ids.indexOf(ch_id4))
					//if(cur_t_ids.indexOf(ch_id4)>=0&&cur_t_ids.indexOf(ch_id3)>=0&&cur_t_ids.indexOf(ch_id2)>=0&&cur_t_ids.indexOf(ch_id1)>=0){
					if(triangleMesh[ch_id4].visible&&triangleMesh[ch_id3].visible&&triangleMesh[ch_id2].visible&&triangleMesh[ch_id1].visible){
					//константное растояние по уровню тайла предка
				    activatedDistPrnt=10.0-((tiles[prntId].lvl+1)*1.5)
					console.debug("activatedDistPrnt "+activatedDistPrnt)
					//растояние к всем чайлдам больше константного к предку
				 	if(getDistance(cam,ch_id4)>activatedDistPrnt&&getDistance(cam,ch_id3)>activatedDistPrnt&&getDistance(cam,ch_id2)>activatedDistPrnt&&getDistance(cam,ch_id1)>activatedDistPrnt){flagRise=true;}
                    //поднимаемся на уровень выше
				    if(flagRise){					   
					  //prnt_indx=cur_t_ids.indexOf(prntId);
					  //if(prnt_indx<0){
					  //wrt("add prnt "+(prntId))
					  //triangleMesh[prntId] = new THREE.Mesh(tiles[prntId].triangleGeometry, triangleMaterial);
				      //triangleMesh[prntId].position.set(0.0, 0.0, 0.0);
				      //scene.add(triangleMesh[prntId]);
					  //console.debug("prntId add "+prntId)
					  triangleMesh[prntId].visible=true;
					  //cur_t_ids.push(prntId);
					  //}
                     //wrt("del ch "+(ch_id1))
					 //wrt("del ch "+(ch_id2))
					 //wrt("del ch "+(ch_id3))
					 //wrt("del ch "+(ch_id4))
					 /*del_indx=cur_t_ids.indexOf(ch_id1);cur_t_ids.splice(del_indx,1);
					 del_indx=cur_t_ids.indexOf(ch_id2);cur_t_ids.splice(del_indx,1);
				 	 del_indx=cur_t_ids.indexOf(ch_id3);cur_t_ids.splice(del_indx,1);
					 del_indx=cur_t_ids.indexOf(ch_id4);cur_t_ids.splice(del_indx,1);
					 cur_t_ids.push(prntId);*///console.debug("prntId add indx "+cur_t_ids.indexOf(prntId))
					 //del_indx=cur_t_ids.indexOf(ch_id1);
					 //console.debug("cur_t_ids.lenght "+cur_t_ids.length)
					 /*del_indx=cur_t_ids.indexOf(ch_id1);if(del_indx>=0)cur_t_ids.splice(del_indx,1);
					 del_indx=cur_t_ids.indexOf(ch_id2);if(del_indx>=0)cur_t_ids.splice(del_indx,1);
				 	 del_indx=cur_t_ids.indexOf(ch_id3);if(del_indx>=0)cur_t_ids.splice(del_indx,1);
					 del_indx=cur_t_ids.indexOf(ch_id4);if(del_indx>=0)cur_t_ids.splice(del_indx,1);
					 cur_t_ids.unshift(prntId);*/
					 //cur_t_ids.splice(del_indx,4,tiles[id].prnt);//cur_t_ids.push(prntId);
					 /*console.debug("cur_t_ids.lenght "+cur_t_ids.lenght)
					 console.debug("tiles[prntId].prnt "+prntId)
					 console.debug("cur_t_ids.indexOf(prntId) "+cur_t_ids.indexOf(prntId))*/
					 triangleMesh[ch_id1].visible=false;
					 triangleMesh[ch_id2].visible=false;
					 triangleMesh[ch_id3].visible=false;
					 triangleMesh[ch_id4].visible=false;
					 //scene.remove(triangleMesh[ch_id1]);//renderer.deallocateObject(triangleMesh[ch_id1]);
					 //scene.remove(triangleMesh[ch_id2]);//renderer.deallocateObject(triangleMesh[ch_id2]);
					 //scene.remove(triangleMesh[ch_id3]);//renderer.deallocateObject(triangleMesh[ch_id3]);
					 //scene.remove(triangleMesh[ch_id4]);//renderer.deallocateObject(triangleMesh[ch_id4]);
				   
				               }
				                   }
				                      }
	   
			
			}
			
			function lon2tile(lon,zoom) {
			     return (Math.floor((lon+180)/360*Math.pow(2,zoom)));
			 }
            function lat2tile(lat,zoom)  { 
			    return (Math.floor((1-Math.log(Math.tan(lat*Math.PI/180) + 1/Math.cos(lat*Math.PI/180))/Math.PI)/2 *Math.pow(2,zoom)));
			}



		</script>

	</body>
</html>
