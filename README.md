# wage_increase_calc
calculate difference in pure wage (ie. after salary raise) according to czech law

1. have PHP installed
1. run the script
```
./wage_increase_calc wage-level raise-level year-month [untaxables-sum] [vacation-rate vacation-days-utilized] [DEBUG]
```
For example like this:
```
./wage_increase_calc 25000 2000 2018-04 500 147.75 1 DEBUG
```

* the commmand line assumes _current_ rough wage is 25000 and is calculating a difference in pure wage after a salary raise of 2000.
* regards wage calculation for april of 2018.
* untaxables-sum include incomes excluded from tax processing like amortization of employees' tools, untaxed insurance, etc (optional)
* then follows the vacation hourly rate and vacation days utilized within the month of calculation. In case there is no vacation these can be omitted (optional)
* input params validation is simple so just comply instead of quiche rant about runtime validation;-)
* provide `DEBUG` 7th parameter in order to be printed calculation details
* TODO? Find out how employee yearly discount actually works - it is hardcoded as of now, so the calculation can yield not-so-precise results for wages under ~ 13000. IMHO I think only actually taxed amount can be discounted, but am not really sure.
