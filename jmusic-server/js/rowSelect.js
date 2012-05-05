var selectedRows = new Array();
var lastClickedRow;
var mouseDown = false;
var dragging = false;
var rowBeingDragged = null;
var dragInsertIndex = -1;
var tableId = "";
var dragDropEnabled = false;

function initRowSelection(tableName, dragDrop)
{
	tableId = tableName;
	dragDropEnabled = dragDrop;
	
	var rows = document.getElementById(tableId).getElementsByTagName("tbody")[0].getElementsByTagName("tr");
	var chkbox;
	var row;
	
	for (var i = 0; i < rows.length; i++)
	{
		chkbox = getCheckBox(rows[i]);
		
		if (chkbox != null)
		{
			rows[i].addEventListener("click", rowClick, false);
			chkbox.addEventListener("click", checkClick, false);
			
			if (dragDropEnabled)
			{
				rows[i].addEventListener("mousedown", rowMdwn, false);
				rows[i].addEventListener("mouseup", rowMup, false);
				rows[i].addEventListener("mousemove", rowMmove, false);
				document.body.addEventListener("mousemove", bodyMmove, false);
				document.body.addEventListener("mouseup", bodyMup, false);
			}
			
		}
	}
}

function getCheckBox(row)
{
	var inputs = row.getElementsByTagName("input");
	
	for (var i = 0; i < inputs.length; i++)
	{
		if (inputs[i].type == "checkbox" && inputs[i].name=="songs[]")
			return inputs[i];
	}
	
	return null;
}

function rowClick(e)
{
		if (e.altKey) return;
		
		if (navigator.appName.indexOf("Microsoft Internet") != -1 && e.button == 1 || e.button == 0)
		{
			if (e.shiftKey && !e.ctrlKey)
			{
				
				clearSelection();
				
				var tableRows = document.getElementById(tableId).getElementsByTagName("tbody")[0].getElementsByTagName("tr");
				
				var low = Math.min(lastClickedRow.rowIndex, e.currentTarget.rowIndex);
				var high = Math.max(lastClickedRow.rowIndex, e.currentTarget.rowIndex);
				
				for (var i = low; i <= high; i++)
				{
					addSelection(tableRows[i - 2]);
				}
			}
			else if (e.shiftKey && e.ctrlKey)
			{
				var tableRows = document.getElementById(tableId).getElementsByTagName("tbody")[0].getElementsByTagName("tr");
				
				var low = Math.min(lastClickedRow.rowIndex, e.currentTarget.rowIndex);
				var high = Math.max(lastClickedRow.rowIndex, e.currentTarget.rowIndex);
				
				if (selectionIndex(lastClickedRow) != -1)
				{
					for (var i = low; i <= high; i++)
						addSelection(tableRows[i - 2]);
				}
				else
				{
					for (var i = low; i < high; i++)
						removeFromSelection(tableRows[i - 2]);
					
					addSelection(tableRows[high - 2]);
				}
			}
			else if (e.ctrlKey)
			{
				window.getSelection().removeAllRanges();
				toggleSelection(e.currentTarget);
			}
			else
			{
				clearSelection();
				addSelection(e.currentTarget);
			}
		}
		
		window.getSelection().removeAllRanges();
		
		if (!e.shiftKey) lastClickedRow = e.currentTarget;
}

function rowMdwn(e)
{
	mouseDown = true;
	if (selectionIndex(e.currentTarget) != -1)
	{
		rowBeingDragged = e.currentTarget;
	}
}

function rowMmove(e)
{
	if (mouseDown && rowBeingDragged != null && e.currentTarget != rowBeingDragged)
	{
		window.getSelection().removeAllRanges();
		dragging = true;
	}
	
	if (dragging)
	{
		var lastDragInsertIndex = dragInsertIndex;
		
		if (mouseCoord(e).y - elemPos(e.currentTarget).y < 0.5 * e.currentTarget.clientHeight)
			dragInsertIndex = e.currentTarget.rowIndex - 2;
		else
			dragInsertIndex = e.currentTarget.rowIndex - 1;
		
		if (lastDragInsertIndex != dragInsertIndex)
		{
			highlightBorder(lastDragInsertIndex, "#000000");
			highlightBorder(dragInsertIndex, "#FFFFFF");
		}
		
		if (window.event) window.event.cancelBubble = true;
		e.stopPropagation();
	}
}

function highlightBorder(idx, color)
{
	var bodyRows = document.getElementById(tableId).getElementsByTagName("tbody")[0].getElementsByTagName("tr");
	var headerRows = document.getElementById(tableId).getElementsByTagName("thead")[0].getElementsByTagName("tr");
	
	if (idx == 0)
	{
		headerRows[headerRows.length - 1].style.borderBottomColor = color;
		bodyRows[0].style.borderTopColor = color;
	}
	else if (idx > 0 && idx < bodyRows.length)
	{
		bodyRows[idx - 1].style.borderBottomColor = color;
		bodyRows[idx].style.borderTopColor = color;
	}
	else if (idx == bodyRows.length)
	{
		bodyRows[idx - 1].style.borderBottomColor = color;
	}
}

function mouseCoord(e)
{
	var posx = 0;
	var posy = 0;
	
	if (e.pageX || e.pageY)
	{
		posx = e.pageX;
		posy = e.pageY;
	}
	else
	{
		posx = e.clientX + document.body.scrollLeft;
		posy = e.clientY + document.body.scrollTop;
	}
	
	return {x: posx, y: posy};
}

function elemPos(obj)
{
	var curleft = curtop = 0;
	
	if (obj.offsetParent)
	{
		do
		{
			curleft += obj.offsetLeft;
			curtop += obj.offsetTop;
		} while (obj = obj.offsetParent);
		
		return {x: curleft, y: curtop};
	}
}

function bodyMup(e)
{
	mouseDown = false;
	rowBeingDragged = null;
	dragging = false;
	dragInsertIndex = -1;
}

function bodyMmove(e)
{
	if (dragging && dragInsertIndex >= 0)
		highlightBorder(dragInsertIndex, "#000000");
	
	dragInsertIndex = -1;
}

function rowMup(e)
{
	if (dragging)
	{
		highlightBorder(dragInsertIndex, "#000000");
		var elem = document.createElement("input");
		elem.setAttribute("type", "hidden");
		elem.setAttribute("name", "insertionPoint");
		elem.setAttribute("value", dragInsertIndex);
		var songForm = document.getElementById("songsForm");
		songForm.appendChild(elem);
		document.getElementById("action").value = "rearrange";
		songForm.submit();
	}
	mouseDown = false;
	rowBeingDragged = null;
	dragging = false;
	dragInsertIndex = -1;
}

function checkClick(e)
{
	var row = e.currentTarget.parentNode;
	
	while (row.nodeName != "TR")
		row = row.parentNode;
	
	toggleSelection(row);
	
	if (window.event) window.event.cancelBubble = true;
	e.stopPropagation();
	lastClickedRow = e.currentTarget;
}

function clearSelection()
{
	var row;
	
	while (selectedRows.length)
	{
		row = selectedRows.pop();
		row.style.backgroundColor = "";
		tickCheckbox(row, false);
	}
}

function tickCheckbox(row, checked)
{
	var inputs = row.getElementsByTagName("input");
	for (var i = 0; i < inputs.length; i++)
	{
		if (inputs[i].type == "checkbox")
			inputs[i].checked = checked;
	}
}

function selectionIndex(row)
{
	for (var i = 0; i < selectedRows.length; i++)
	{
		if (selectedRows[i] == row)
			return i;
	}
	
	return -1;
}

function toggleSelection(row)
{
	var i = selectionIndex(row);
	
	if (i != -1)
	{
		selectedRows.splice(i, 1);
		row.style.backgroundColor = "";
		tickCheckbox(row, false);
		return;
	}
	
	addSelection(row);
}

function addSelection(row)
{
	selectedRows.push(row);
	row.style.backgroundColor = "#00AAFF";
	tickCheckbox(row, true);
}

function removeFromSelection(row)
{
	var i = selectionIndex(row);
	
	if (i != -1)
	{
		selectedRows.splice(i, 1);
		row.style.backgroundColor = "";
		tickCheckbox(row, false);
		return;
	}
}