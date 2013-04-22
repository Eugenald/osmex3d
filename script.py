from lxml import etree
from datetime import *
from math import *
from copy import *
import MySQLdb

HTML_FILE_BEGIN = """<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>GPS track Editor v.1.0</title>
    <script src="OpenLayers.js"></script>
    <script type="text/javascript">
      var map;
      var mapnik;
      var fromProjection;
      var toProjection;
      var markers;
      var x,s_x, t_x, y,i;
      var lon=[];
      var lat=[];
      function init() {
        x=0;y=0;i=0;
        map = new OpenLayers.Map({
          div: "basicMap",
          allOverlays: true
        });
        mapnik         = new OpenLayers.Layer.OSM();
        fromProjection = new OpenLayers.Projection("EPSG:4326");   // Transform from WGS 1984
        toProjection   = new OpenLayers.Projection("EPSG:900913"); // to Spherical Mercator Projection
        var position       = new OpenLayers.LonLat(30.6977847,46.4409529).transform( fromProjection, toProjection);
        var zoom           = 10;

        map.addLayer(mapnik);
        map.setCenter(position, zoom );
        map.addControl(new OpenLayers.Control.LayerSwitcher());

        markers = new OpenLayers.Layer.Markers( "Markers" );
        map.addLayer(markers);

        var size = new OpenLayers.Size(20,20);
        var offset = new OpenLayers.Pixel(-(size.w/2), -(size.h/2));
        var icon = new OpenLayers.Icon('http://i062.radikal.ru/1209/58/9d99454e85d0.png',size,offset);
		var icon_start = new OpenLayers.Icon('http://s09.radikal.ru/i182/1212/ad/590aa0e89cae.png',size,offset);
		var icon_second = new OpenLayers.Icon('http://s45.radikal.ru/i108/1212/0d/b2cbfeb346ac.png',size,offset);
		"""
HTML_FILE_END = """
	for ( i = 0; i < 1500; i+=2){
		marker_tmp = new OpenLayers.Marker(new OpenLayers.LonLat(s_x[i+1], s_x[i]).transform( fromProjection, toProjection),icon_start.clone());
		markers.addMarker(marker_tmp);
	}
	for ( i = 0; i < 0; i+=2){
		marker_tmp = new OpenLayers.Marker(new OpenLayers.LonLat(t_x[i+1], t_x[i]).transform( fromProjection, toProjection),icon_second.clone());
		markers.addMarker(marker_tmp);
	}
	for ( i = 0; i < 0; i+=2){
		marker_tmp = new OpenLayers.Marker(new OpenLayers.LonLat(x[i+1], x[i]).transform( fromProjection, toProjection),icon.clone());
		markers.addMarker(marker_tmp);
	}
      map.events.register("click", map , function(e){
        marker_tmp = new OpenLayers.Marker(new OpenLayers.LonLat(map.getLonLatFromPixel(e.xy).lon, map.getLonLatFromPixel(e.xy).lat),icon.clone());
        markers.addMarker(marker_tmp);
        var lonlat = new OpenLayers.LonLat(map.getLonLatFromPixel(e.xy).lon, map.getLonLatFromPixel(e.xy).lat).transform( toProjection, fromProjection);
        lon[i] = lonlat.lon;
        lat[i] = lonlat.lat;
        i++;
      });

      }

  </script>
  </head>
  <body onLoad="init();">
      <div id="basicMap"></div>
  </body>
</html>"""

def parseNodes(file):
    tree = etree.parse(file)
    nodes = tree.xpath('/osm/node')
    node_list = []
    for node in nodes:
        id = node.get('id')
        lat = float(node.get('lat'))
        lon = float(node.get('lon'))
        tmp_list = [id, lat, lon]
        node_list.append(tmp_list)
    return node_list

def parseWays(file):
    tree = etree.parse(file)
    ways = tree.xpath('/osm/way')
    bounds = tree.xpath('/osm/bounds')
    boundsArray = []
    boundsArray.append(bounds[0].get('minlat'))
    boundsArray.append(bounds[0].get('maxlat'))
    boundsArray.append(bounds[0].get('minlon'))
    boundsArray.append(bounds[0].get('maxlon'))
    way_list = []
    way_list.append(boundsArray)
    for way in ways:
        id = way.get('id')
        #print id
        nodes = tree.xpath('/osm/way[@id=%s]/nd' % id)
        way_nodes = []
        for node in nodes:
            way_nodes.append(node.get('ref'))
        tags = tree.xpath('/osm/way[@id=%s]/tag' % id)
        for tag in tags:
            k = tag.get('k')
            if k == 'building' or k == 'amenity':
                way_list.append([id, way_nodes])
                break
    return way_list

def searchNode(node_id, nodes):
    for node in nodes:
        if node[0] == node_id:
            return node
    return 0

def checkNodeAngle(firstNode, secondNode):
    a = secondNode[2] - firstNode[2]
    b = secondNode[1] - firstNode[1]
    c = sqrt(a*a+b*b)
    if c == 0:
        angle = 0
    else:
        angle = asin(a/c)
    #grad = 180 - (90 +fabs((angle * 180) / pi))
    return (angle * 180) / pi

def calculateDistance(firstNode, secondNode):
    return (111.2 * sqrt(pow((firstNode[1]-secondNode[1]),2)
                         + pow((firstNode[2]-secondNode[2])*cos(pi*firstNode[1]/180),2))*1000)

def searchLeftVector(rectangle):
    index = 0
    #print "rectangle: ",rectangle
    for i in range(0, len(rectangle) - 1):
        if rectangle[i][2] < rectangle[index][2]:
            index = i
    last_index = 0
    rectangle_n = copy(rectangle)
    del rectangle_n[index]
    for i in range(0, len(rectangle_n) ): #MAGIC! why not for len(rectangle_n) -1
        if (rectangle_n[i][2] < rectangle_n[last_index][2]):
            last_index = i
    if rectangle[index][1] < rectangle_n[last_index][1]:
        return [rectangle[index], rectangle_n[last_index]]
    else:
        return [rectangle_n[last_index], rectangle[index]]

def searchRightVector(rectangle):
    index = 0
    #print "rectangle: ",rectangle
    for i in range(0, len(rectangle) - 1):
        if rectangle[i][2] > rectangle[index][2]:
            index = i
    last_index = 0
    rectangle_n = copy(rectangle)
    del rectangle_n[index]
    for i in range(0, len(rectangle_n) ): #MAGIC! why not for len(rectangle_n) -1
        if (rectangle_n[i][2] > rectangle_n[last_index][2]):
            last_index = i
    if rectangle[index][1] < rectangle_n[last_index][1]:
        return [rectangle[index], rectangle_n[last_index]]
    else:
        return [rectangle_n[last_index], rectangle[index]]

def searchLongVector(rectangle):
    vector = [rectangle[0], rectangle[1]]
    lenvect = calculateDistance(vector[0], vector[1])
    for i in range(0, len(rectangle)-1):
        if (lenvect < calculateDistance(rectangle[i], rectangle[i+1])):
            vector = [rectangle[i], rectangle[i+1]]
            lenvect = calculateDistance(rectangle[i], rectangle[i+1])
    if vector[0][1] > vector[1][1]:
        return [vector[1], vector[0]]
    else:
        return [vector[0], vector[1]]

def calculateAngleOffset(boundbox, rectangle):
    bottomLine = [ [float(boundbox[0]), float(boundbox[2])], [float(boundbox[0]), float(boundbox[3])] ]
    bottomLine[0][0] -= 0.003
    bottomLine[1][0] -= 0.003
    xx = searchLeftVector(rectangle)
    yy = searchRightVector(rectangle)
    center_left = [ (xx[0][1] + xx[1][1]) / 2 , (xx[0][2] + xx[1][2]) / 2 ]
    center_right = [ (yy[0][1] + yy[1][1]) / 2 , (yy[0][2] + yy[1][2]) / 2 ]
    if center_left[0] > center_right[0]:
        center_line = [center_right, center_left]
    else:
        center_line = [center_left, center_right]
        #print "CENTER LINE: ", [center_right, center_left]
    #print "bottomLine: ", bottomLine
    #print "building: ", buildingLine
    v1x = bottomLine[1][0] - bottomLine[0][0]
    v1y = bottomLine[1][1] - bottomLine[0][1]
    v2x = center_line[1][0] - center_line[0][0]
    v2y = center_line[1][1] - center_line[0][1]
    #print "vectors: ", v1x, v1y, v2x, v2y
    angle = acos((v1x*v2x + v1y*v2y) / (sqrt(pow (v1x,2) + pow (v1y,2)) * sqrt(pow (v2x,2) + pow (v2y,2))))
    horizontalAngle = ((angle*180)/pi)
    #print "horizontal angle1", horizontalAngle
    angle =  (((horizontalAngle)*pi)/180)
    return angle

def calculateAngleOffsetBottom(boundbox, rectangle):
    bottomLine = [ [float(boundbox[0]), float(boundbox[2])], [float(boundbox[0]), float(boundbox[3])] ]
    bottomLine[0][0] -= 0.003
    bottomLine[1][0] -= 0.003
    xx = searchLeftVector(rectangle)
    yy = searchRightVector(rectangle)
    #print "WARNING! ", xx, "!!!!!!!!!", yy
    buildingLine = [ yy[0], xx[0] ]
        #print "CENTER LINE: ", [center_right, center_left]
    #print "bottomLine: ", bottomLine
    #print "building: ", buildingLine
    v1x = bottomLine[1][0] - bottomLine[0][0]
    v1y = bottomLine[1][1] - bottomLine[0][1]
    v2x = buildingLine[1][1] - buildingLine[0][1]
    v2y = buildingLine[1][2] - buildingLine[0][2]
    #print "vectors: ", v1x, v1y, v2x, v2y
    angle = acos((v1x*v2x + v1y*v2y) / (sqrt(pow (v1x,2) + pow (v1y,2)) * sqrt(pow (v2x,2) + pow (v2y,2))))
    horizontalAngle = 180 - ((angle*180)/pi)
    #print "horizontal angle1", horizontalAngle
    angle =  (((horizontalAngle)*pi)/180)
    return angle

def calculateAngleOffsetSecond(boundbox, rectangle):
    bottomLine = [ [float(boundbox[0]), float(boundbox[2])], [float(boundbox[0]), float(boundbox[3])] ]
    bottomLine[0][0] -= 0.003
    bottomLine[1][0] -= 0.003
    buildingLine = searchLeftVector(rectangle)
    #print "bottomLine: ", bottomLine
    #print "building: ", buildingLine
    v1x = bottomLine[1][0] - bottomLine[0][0]
    v1y = bottomLine[1][1] - bottomLine[0][1]
    v2x = buildingLine[1][1] - buildingLine[0][1]
    v2y = buildingLine[1][2] - buildingLine[0][2]
    #print "vectors: ", v1x, v1y, v2x, v2y
    angle = acos((v1x*v2x + v1y*v2y) / (sqrt(pow (v1x,2) + pow (v1y,2)) * sqrt(pow (v2x,2) + pow (v2y,2))))
    horizontalAngle = (angle*180)/pi
    #print "horizontal angle2", horizontalAngle
    verticalLine = [ [float(boundbox[0]), float(boundbox[2])], [float(boundbox[1]), float(boundbox[2])] ]
    verticalLine[0][1] -= 0.003
    verticalLine[1][1] -= 0.003
    #print "verticalLine: ", verticalLine
    v1x = verticalLine[1][0] - verticalLine[0][0]
    v1y = verticalLine[1][1] - verticalLine[0][1]
    v2x = buildingLine[1][1] - buildingLine[0][1]
    v2y = buildingLine[1][2] - buildingLine[0][2]
    #print "vectors: ", v1x, v1y, v2x, v2y
    angleNew = acos((v1x*v2x + v1y*v2y) / (sqrt(pow (v1x,2) + pow (v1y,2)) * sqrt(pow (v2x,2) + pow (v2y,2))))
    if horizontalAngle < 90:
        angleNew *= -1
    verticalAngle = (angleNew*180)/pi
    #print "vertical angle", verticalAngle
    return angleNew

def createRectangle(boundBox, building, database):
    #print building
    BGN = building[0]
    END = building[2]
    center_x = BGN[1] + ((END[1] - BGN[1]) / 2)
    center_y = BGN[2] + ((END[2] - BGN[2]) / 2)
    leftLine = searchLeftVector(building)
    rightLine = searchRightVector(building)
    topLine = [leftLine[1], rightLine[1]]
    bottomLine = [leftLine[0], rightLine[0]]

    if calculateDistance(leftLine[0], leftLine[1]) > calculateDistance(rightLine[0], rightLine[1]):
        Z_COORD = calculateDistance(leftLine[0], leftLine[1]) / 2
    else:
        Z_COORD = calculateDistance(rightLine[0], rightLine[1]) / 2

    Y_COORD = 20

    if calculateDistance(topLine[0], topLine[1]) > calculateDistance(bottomLine[0], bottomLine[1]):
        X_COORD = calculateDistance(topLine[0], topLine[1]) / 2
    else:
        X_COORD = calculateDistance(bottomLine[0], bottomLine[1]) / 2

    angle1 = calculateAngleOffsetBottom(boundBox, building)
    angle2 = calculateAngleOffsetSecond(boundBox, building)
    print "angle ", angle1, " angle2 ", angle2
    constant = 1
    if angle2 < 0:
        constant *= -1
    angleOffset = ((abs(angle1) + abs(angle2))/2) * constant
    INSERT_RECTANGLE = "INSERT INTO objectInstance VALUES (null, %f, %f, %f, \
    %f, %f, %f, %f, %f, %d);" % (X_COORD, Y_COORD, Z_COORD, 0.0, angleOffset, 0.0,
        center_x, center_y, 1)
    database.execute(INSERT_RECTANGLE)
    return [center_x, center_y]

def parseBuildingsData(nodes, ways):
    buildingArray = []
    counter = 0
    for building in ways:
        count_nodes = len(building[1])
        node_index = 0
        counter += 1
        print "parsing %d of %d" % (counter, len(ways))
        nodesArray = []
        previousAngle = -200
        while node_index < count_nodes:
            if (node_index == count_nodes - 1) and (building[1][0] == building[1][node_index]):
                break
            node_id = building[1][node_index]
            node = searchNode(node_id, nodes)
            if node_index != 0 and count_nodes > 4 and (building[1][0] != building[1][4]):
                angle = checkNodeAngle(searchNode(building[1][node_index - 1], nodes), node)
                if fabs(previousAngle - angle) > 15:
                    nodesArray.append(node)
                    previousAngle = angle
                else:
                    del nodesArray[len(nodesArray) - 1]
                    nodesArray.append(node)
                    previousAngle = checkNodeAngle(nodesArray[len(nodesArray) - 2], node)
            else:
                nodesArray.append(node)
            node_index += 1
        buildingArray.append(nodesArray)
    return buildingArray

print "Please, enter file name: "
FILE_NAME = raw_input()
print("\n[%s] Start parsing...Please wait!\n" % datetime.today().strftime('%H:%M:%S'))
node_list = parseNodes(FILE_NAME)
print("[%s] Parse nodes...Done!\n" % datetime.today().strftime('%H:%M:%S'))
way_list = parseWays(FILE_NAME)
bounds = way_list[0]
del way_list[0]
print("[%s] Parse ways...Done!\n" % datetime.today().strftime('%H:%M:%S'))
print("[%s] Start parsing file\n" % datetime.today().strftime('%H:%M:%S'))
buildingArray = parseBuildingsData(node_list, way_list)
print("[%s] End parsing file\n" % datetime.today().strftime('%H:%M:%S'))

buildings_center = []
db = MySQLdb.connect(host="127.0.0.1", user="root", port = 3306, passwd="vertrigo", charset='utf8')
connection = db.cursor()
connection.execute("USE osmex3d;")
#buildings_center.append(createRectangle(bounds, buildingArray[14], connection))
#buildings_center.append([46.438827, 30.697023])
#buildings_center.append([46.4388899, 30.6971695])
for building in buildingArray:
    if len(building) <= 5 and len(building) > 3:
       buildings_center.append(createRectangle(bounds, building, connection))
db.commit()
db.close()

print("[%s] End processing data" % datetime.today().strftime('%H:%M:%S'))

coord_start = []
coord_second = []
file = open("./OpenLayers/openlayers/result.html", "w")
file.write("%s" % HTML_FILE_BEGIN)
file.write("x=[")
file.write("];\n")
t = 1
file.write("\t\ts_x = [%s, %s " % (buildings_center[0][0], buildings_center[0][1]))
while t < len(buildings_center):
    file.write(",\n\t\t%s, %s" % (buildings_center[t][0], buildings_center[t][1]))
    t += 1
file.write("];\n\t\tt_x = [];")
file.write("%s" % HTML_FILE_END)
file.close()

print "\n\n[%s] Finished!" % datetime.today().strftime('%H:%M:%S')