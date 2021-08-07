## An application to run independent process in Windows system

<br>

### Build:

```
csc /out:chain.exe chain.cs
```

<br>

### Test:

<br>

#### Linux:

```
mono chain.exe php dGVzdC5waHA=
```

**Isn't works properly**

<br>

#### Windows:

```
chain.exe php dGVzdC5waHA=
```

<br>

This command should create a `test.txt` file in current folder after 5 seconds