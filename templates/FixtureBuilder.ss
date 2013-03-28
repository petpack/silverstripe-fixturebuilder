<html>
<head>
<title>Fixture Builder</title>
<style>
	label {
		font-size: 0.8em;
	}
	table {
		border-collapse: collapse;
	}
	table tr {
		background: #eee;
	}
	table tr:nth-child(odd) {
		background: #ccc;
	}
	
	th {
		text-align: left;
	}
	td, th {
		padding: 0px 3px;
	}
	table th {
		background: #000;
		color: #fff;
	}
</style>
</head>
<body>
	<h1>MySQL Fixture Builder</h1>
	<form method="get" action="/dev/fixture-builder/build">
		<div style="float:left;width: 45%">
			Select the tables you want to dump to YAML:<br />
			Deselect 'All' to only dump the first record.<br />
			<table>
				<tr><th>Table</th>
					<th>Records</th>
					<th>All</th>
				</tr>
			<% control list_tables %>
				<tr><td>
						<label><input type="checkbox" name="table" value="$table" <% if selected == true %> checked="checked" <% end_if %> />$table</label>
					</td><td>$records</td>
					<td><input type="checkbox" name="all" value="$table" <% if small == true %> checked="checked" <% end_if %> /></td>
				</tr>
			<% end_control %>
			</table>
		</div>
		<div style="float:right;width: 45%;margin-right: 10px;">	
			Fields to ignore (format: table.field, case-insensitive, one per line):<br />
			<textarea name="ignore_fields" rows="20" style="width: 100%">$ignored_fields_val</textarea><br />
		</div>
		
		<div style="float:right;width:45%; margin-right: 10px;margin-top: 20px;"">
			<strong>NOTES:</strong>
			<ul>
				<li>This is not intended to give you a fully workable fixture file - you should see the output of this process as a 'starting point' for required data. You should also have fixture file(s) for the data you want to test!</li>
				<li>The YAML output will include ID fields. This is generally "not how it's supposed to be done": you should be using identifiers in your fixtures. But it can be used as a mechanism to ensure that certain records exist.</li>
				<li>No relationships / dependencies are tracked - just because you have a record for a Group doesn't mean you'll have any members for that group, or vice-versa - you might have a member record pointing to a group which doesn't exist.</li>
			</ul>
		</div>
		
		<input style="float: left;clear:left;margin:10px;" type="submit" value="Go!" />
	</form>
</body>
</html>