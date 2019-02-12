# salary_tools
calculate a salary according to czech law

1. have PHP installed
1. run the script
```
./salary_calc wage-level year-month [untaxables-sum] [vacation-rate vacation-days-utilized] [DEBUG]
```
For example like this:
```
./salary_calc 25000 2018-04 500 147.75 1 DEBUG
```

* the commmand line assumes _current_ rough wage is 25000
* regards wage calculation for april of 2018.
* untaxables-sum include incomes excluded from tax processing like amortization of employees' tools, untaxed insurance, etc (optional)
* then follows the vacation hourly rate and vacation days utilized within the month of calculation. In case there is no vacation these can be omitted (optional)
* input params validation is simple so just comply instead of quiche rant about runtime validation;-)
* provide `DEBUG` 7th parameter in order to be printed calculation details
