

<style>

.link {
  stroke: #ccc;
}

.node text {
  pointer-events: none;
  font: 10px sans-serif;
}

</style>

<button class="button" onclick="$('#graph_controls').toggle();">Show graph layout controls</button>

<div id='graph_controls' style='display: none' >
 <input type="text" id="gravityInput" value=".5" />
    Gravity <input id="gravitySlider" type="range" onchange="updateForce(); " min ="0" max="5" step =".01"  value=".13"/>

 <input type="text" id="attractionInput" value="50" />
    Attraction (Links) <input id="attractionSlider" type="range" onchange="updateForce();" min ="-200" max="2000" step ="1"  value="-24"/>

  <input type="text" id="repulsionInput" value="-60" />
    Charge (Nodes) <input id="repulsionSlider" type="range" onchange="updateForce();" min ="-700" max="0" step ="1"  value="-289" /> 


 <input type="text" id="attractionInputG2" value="50" />
    Attraction (Links) <input id="attractionSliderG2" type="range" onchange="updateForce(); " min ="-200" max="2000" step ="1"  value="63"/>

  <input type="text" id="repulsionInputG2" value="-60" />
    Charge (Nodes) <input id="repulsionSliderG2" type="range" onchange="updateForce(); " min ="-700" max="0" step ="1"  value="-183" /> 





<br>
  | <input type="button" onclick="force.start();" value="UnFreeze">
    <input type="button" onclick="force.stop();" value="Freeze">
</div>

<script src="http://d3js.org/d3.v3.min.js"></script>
<script>

function updateForce() {
  force.stop();
  
  var newGravity = document.getElementById('gravitySlider').value;
  var newCharge = document.getElementById('repulsionSlider').value;
  var newLStrength = document.getElementById('attractionSlider').value;
  var newChargeG2 = document.getElementById('repulsionSliderG2').value;
  var newLStrengthG2 = document.getElementById('attractionSliderG2').value;
  
  
  document.getElementById('gravityInput').value = newGravity;
  document.getElementById('repulsionInput').value = newCharge;
  document.getElementById('attractionInput').value = newLStrength;
  document.getElementById('repulsionInputG2').value = newChargeG2;
  document.getElementById('attractionInputG2').value = newLStrengthG2;
  
  
  force
  .charge(
	function (node) {
		if(node.group == 2){
			return newCharge;
		}else{
			return newChargeG2;
		}
	}
)
  .linkDistance(
        function (link) {
                if(link.class == 2){
                        return newLStrength;
                }else{
                        return newLStrengthG2;
                }
        }
	)
  .gravity( newGravity); //gravity does not accept a function as an argument.
  
  
  force.start();
}




//Constants for the SVG
var width = 1400,
    height = 900;

//Set up the colour scale
var color = d3.scale.category20();

//Set up the force layout
var force = d3.layout.force()
//    .charge(function(link){ if(link.class){return 100;}else{return -500;} })
//    .charge(function(node) { if(node.group < 3){ return(-100); }else{ return(-3000); }  } )
    .charge(-500 )
    .gravity(.5)
//    .linkDistance(10)
//    .linkStrength(function(link){ return link.linkStrength})
    .size([width, height]);

//Append a SVG to the body of the html page. Assign this SVG as an object to svg
var svg = d3.select("body").append("svg")
    .attr("width", width)
    .attr("height", height);

//Read the data from the mis element 

//Creates the graph data structure out of the json data

d3.json("<?php echo $json_data; ?>", function(error, graph) { 

	force.nodes(graph.nodes)
    		.links(graph.links)
    		.start();

//Create all the line svgs but without locations yet
	var link = svg.selectAll(".link")
    		.data(graph.links)
    		.enter().append("line")
    		.attr("class", "link")
    		.style("stroke-width", function (d) {
    			return Math.sqrt(d.value);
		});

//Do the same with the circles for the nodes - no 
//Changed
	var node = svg.selectAll(".node")
    		.data(graph.nodes)
    		.enter().append("g")
    		.attr("class", "node")
    		.call(force.drag);

	node.append("circle")
    		.attr("r", function(d) { return d.size})
    		.style("fill", function (d) {
    			return color(d.group);
		})

	node.append("text")
      		.attr("dx", 10)
      		.attr("dy", ".35em")
      		.text(function(d) { return d.name });
//End changed


//Now we are giving the SVGs co-ordinates - the force layout is generating the co-ordinates which this code is using to update the attributes of the SVG elements
	force.on("tick", function () {

		

    		link.attr("x1", function (d) {
        		return d.source.x;
    		})
        	.attr("y1", function (d) {
        		return d.source.y;
    		})
        	.attr("x2", function (d) {
        		return d.target.x;
    		})
        	.attr("y2", function (d) {
        		return d.target.y;
    		});

    		//Changed
    
    		d3.selectAll("circle").attr("cx", function (d) {
        			return d.x;
    			})
        		.attr("cy", function (d) {
        			return d.y;
    			});

    		d3.selectAll("text").attr("x", function (d) {
        			return d.x;
    			})
        		.attr("y", function (d) {
        			return d.y;
    			});
    
    		//End Changed

	});
});

updateForce();

</script>


<div id='content' class='content'>

</div>

	<a href='<?php echo $json_data; ?>'><?php echo $json_data; ?></a>
