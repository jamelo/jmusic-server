var playerWindow = null;

function openPlayWindow(t)
{
	//Returns 0 if window was already opened, returns 1 if new window was opened.
	
	if (playerWindow == null || playerWindow.closed)
	{
		playerWindow = window.open(null, "musicplayer", "toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=yes, copyhistory=no, width=550, height=545");
		
		if (playerWindow.document.getElementById("playerPage") != null)
		{
			return 0;
		}
		else
		{
			playerWindow.location = t;
			return 1;
		}
	}
	else
	{
		return 0;
	}
}

function playSong(song, enqueue)
{
	if (!openPlayWindow("player.php?fileName=" + song))
	{
		if (enqueue)
			playerWindow.sendCommand("enqueue song getfile.php?file=" + song);
		else
		{
			playerWindow.sendCommand("load song getfile.php?file=" + song);
			playerWindow.focus();
		}
	}
	
	return false;
}

function playList(list)
{
	if (!openPlayWindow("player.php?playlist=" + list))
	{
		playerWindow.sendCommand("load playlist getplaylist.php?playlist=" + list);
		playerWindow.focus();
	}
	
	return false;
}

function collapseTable(switchRef, tableId)
{
	var table = document.getElementById(tableId);
	var node = table.firstChild;
	var collapse = false;
	
	if (switchRef.innerHTML == "-")
	{
		collapse = true;
		switchRef.innerHTML = "+";
	}
	else
	{
		switchRef.innerHTML = "-";
	}
	
	while (node)
	{
		if (node.nodeType == 1)
		{
			if (node.nodeName.toLowerCase() == "thead")
			{
				var subnode = node.firstChild;
				
				while (subnode)
				{
					if (subnode.nodeType == 1 && subnode.className != "tableTitle")
						subnode.style.display = collapse ? "none" : "table-row";
					
					subnode = subnode.nextSibling
				}
			}
			else
			{
				if (node.nodeName.toLowerCase() == "tfoot")
					node.style.display = collapse ? "none" : "table-footer-group";
				else
					node.style.display = collapse ? "none" : "table-row-group";
			}
		}
		
		node = node.nextSibling;
	}
}

function createEllipsis()
{
	var noOverflows = document.getElementsByClassName("noOverflow");
	var node;
	
	for (var i = 0; i < noOverflows.length; i++)
	{
		node = document.createElement("span");
		node.setAttribute("class", "ellipsis");
		node.style.display = "none";
		node.appendChild(document.createTextNode("..."));
		noOverflows[i].appendChild(node);
		
		if (noOverflows[i].scrollHeight > noOverflows[i].clientHeight)
			node.style.display = "inline";
	}
	
	window.onresize = function() { refreshEllipsis(); }
}

function refreshEllipsis()
{
	var noOverflows = document.getElementsByClassName("noOverflow");
	var ellipsis;
	
	for (var i = 0; i < noOverflows.length; i++)
	{
		ellipsis = noOverflows[i].getElementsByClassName("ellipsis")[0];
		
		if (noOverflows[i].scrollHeight > noOverflows[i].clientHeight)
			ellipsis.style.display = "inline";
		else
			ellipsis.style.display = "none";
	}
}

function dialogBox(message, title, textbox, buttons, value)
{
	document.getElementById("dialogTitle").innerHTML = title;
	document.getElementById("dialogMessage").innerHTML = message;

	if (textbox)
		document.getElementById("dialogTextBox").style.visibility = "visible";
	else
		document.getElementById("dialogTextBox").style.visibility = "hidden";

	document.getElementById("dialogValue").value = value;
	document.getElementById("dialogTextBox").value = "";

	if (value == "")
		document.getElementById("dialogValue").name = "";
	else
		document.getElementById("dialogValue").name = "songs[0]";
	
	if (buttons == "yesno")
	{
		document.getElementById("dialogOkBtn").value = "Yes";
		document.getElementById("dialogCancelBtn").value = "No";
	}
	else
	{
		document.getElementById("dialogOkBtn").value = "OK";
		document.getElementById("dialogCancelBtn").value = "Cancel";
	}

	document.getElementById("dialogContainer").style.visibility = "visible";
}