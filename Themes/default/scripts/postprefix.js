/**
 * @package SMF Post Prefix
 * @version 4.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license MIT
 */

// Boards select
var select = document.getElementById('board');

// Prefixes options
var options = document.getElementById('select_prefixes').options;
// Prefixes
var prefixes = [[]];

// Store the prefixes in an array
for (var i = 0; i < options.length; i++)
{
	// prefixes.push(options[i].id);
	if (options[i].value == '0')
	{
		continue;
	}
	prefixes[i] = [options[i].id,  document.getElementById(options[i].id).dataset.boards.split(',').map(Number)];
}
prefixes.shift();

// On first load, send the first board
hidePrefixes(post_first_board);

// Now, check if we are changing boards
if (select.addEventListener)
{
	select.addEventListener('change', function() {
		hidePrefixes(select.value);
	});
}

// Hide or show the prefixes
function hidePrefixes(board)
{
	prefixes.forEach(function(prefix) {
	for (var i = 0; i < prefix[1].length; i++)
	{
		// Check if this board is in the prefix
		if (prefix[1][i] == board)
		{
			// Display the prefix
			document.getElementById(prefix[0]).removeAttribute('style');
			// We found the board, so we can stop the loop
			break;
		}
		// Hide the prefix for this board
		document.getElementById(prefix[0]).style.display = 'none';
	}
	});
}