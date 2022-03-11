/**
 * @package SMF Post Prefix
 * @version 4.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license MIT
 */

// Boards select
var select = document.getElementById('board');
// Prefixes select
var prefixes_select = document.getElementById('select_prefixes');
// Prefixes options
var prefix_options = prefixes_select.options;
// Prefixes
var prefixes = [[]];

// Store the prefixes in an array, when using select
if (!prefixes_radio_select)
{
	for (var i = 0; i < prefix_options.length; i++)
	{
		// Ignore the "No Prefix" option
		if (prefix_options[i].value == '0')
			continue;

		// Add the prefix to the array
		prefixes[i] = [prefix_options[i].id,  document.getElementById(prefix_options[i].id).dataset.boards.split(',').map(Number)];
	}
}

// When using radio
if (prefixes_radio_select)
{
	var radio_prefixes = document.querySelectorAll('[id^="prefix_"]');
	radio_prefixes.forEach(function(prefix)
	{
		// Ignore the "No Prefix" option
		if (prefix.value == '0')
			return;

		// Add the prefix to the list
		prefixes.push([prefix.id, prefix.dataset.boards.split(',').map(Number)]);
	});
}

// Remove first entry
prefixes.shift();

// On first load, send the first board
hidePrefixes(post_first_board);

// Now, check if we are changing boards
if (select.addEventListener)
{
	select.addEventListener('change', function()
	{
		// For select, select the first option
		if (!prefixes_radio_select)
			prefixes_select.value = '0';

		// For radio, check the first input
		else
			document.getElementById('prefix_0').checked = true;

		// Hide the prefixes!
		hidePrefixes(select.value);
	});
}

// Hide or show the prefixes
function hidePrefixes(board)
{
	for (var i = 0; i < prefixes.length; i++)
	{
		for (var j = 0; j < prefixes[i][1].length; j++)
		{
			// Check if this board is in the prefix
			if (prefixes[i][1][j] == board)
			{
				// Display the prefix
				document.getElementById(prefixes[i][0]).removeAttribute('style');

				// For radio, display the actual prefix
				if (prefixes_radio_select)
				{
					// Display the prefix 
					document.getElementById(prefixes[i][0]).nextElementSibling.style.display = 'inline-block';
					// Add some margin
					document.getElementById(prefixes[i][0]).parentElement.style.marginRight = '10px';
				}

				// We found the board, so we can stop the loop
				break;
			}

			// Hide prefixes that don't belong to this board
			document.getElementById(prefixes[i][0]).style.display = 'none';

			// For radio, hide the actual prefix
			if (prefixes_radio_select)
			{
				// Hide the prefix
				document.getElementById(prefixes[i][0]).nextElementSibling.style.display = 'none';
				// Remove the margin so avoid empty space
				document.getElementById(prefixes[i][0]).parentElement.style.marginRight = '0';
			}
		}
	}
}