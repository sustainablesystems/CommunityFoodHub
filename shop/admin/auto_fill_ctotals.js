function form_auto_fill ()
{
  var raw_data = document.getElementById("auto_fill_box").value;
  var batchno = document.getElementById("auto_fill_batchno").value;
  var lines = raw_data.split(/\r?\n/g);
  var errors = "";
  for (var i = 0; i < lines.length; i++)
  {
    var values = lines[i].split(/ ?\t ?/);
    if (values[0]) values[0] = values[0].replace(/[^\d]/g, "");
    if (values[1]) values[1] = values[1].replace(/[^\d.-]/g, "");
    if (values[2]) values[2] = values[2].replace(/[^\d.-]/g, "");
    var invoice_ref = document.getElementById("shopping_amount"+values[0]);
    var mem_ref = document.getElementById("membership_amount"+values[0]);
    var batchno_ref = document.getElementById("batchno"+values[0]);
    if (invoice_ref && mem_ref && (values[1] || values[2]))
    {
      if (values[1]) invoice_ref.value = values[1];
      if (values[2]) mem_ref.value = values[2];
      if (batchno_ref) batchno_ref.value = batchno;
    } else if ((!invoice_ref && values[1]) || (!mem_ref && values[2])) {
      errors += "Couldn't find input box to fill for member #"+values[0]+"\n";
    }
  }
  if (errors != "") alert(errors);
}
