unit UTemplate;

interface
uses
  strutils, common, params, uIntList, sysutils, classes, dialogs, msxml;
  const
    Codes64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

type TTemplate=class
  private       
    offset: integer;
    function GetIndex(s:char):integer;
  public
    Code: string;
    Bin: string;
    IDProf1: integer;
    IDProf2: integer;
    Prof1: string;
    Prof2: string;
    NbAttr: integer;
    SizeAttr: integer;
    SkillSize: integer;
    BinDump : string;
    Attributes :TStringList;
    AttributesValue :TIntegerList;
    SkillsID :TIntegerList;
    CodeOK: boolean;
    procedure setProf(P1, P2: string);
    function binval(S,D: string): integer;
    function tronque(S: string;n:integer): string;
    procedure CodeToBin;
    procedure BinToCode;
    procedure Analyse;
    procedure RAZ;
    procedure MakeBuild;
    constructor Create;
    destructor Destroy;
    function GWBBCode: string;
    function BBCode: string;
    function valbin(I, N: integer;D:string): string;
end;

implementation         
//-------------------------------------------------------
procedure TTemplate.setProf(P1, P2: string);
begin
  idprof1 := GetProfIdByAbbr(p1);
  prof1 := p1;                  
  if idprof1=0 then prof1 := '';
  idprof2 := GetProfIdByAbbr(p2);
  prof2 := p2;
  if idprof2=0 then prof2 := '';
end;
//-------------------------------------------------------
destructor TTemplate.Destroy;
begin
  Attributes.Free;
  AttributesValue.Free;
  SkillsID.Free;
end;
//-------------------------------------------------------
constructor TTemplate.Create;
begin
  Attributes := TStringList.Create;
  AttributesValue := TIntegerList.Create;
  SkillsID := TIntegerList.Create;
  RAZ;
end;
//-------------------------------------------------------
function TTemplate.tronque(S: string;n:integer): string;
begin
  result := MidStr(S, n+1, length(s)-n);
end;
//-------------------------------------------------------
function TTemplate.binval(S, D: string): integer;
const CRLF = #13#10;
var r : string;
begin
  r := strrev(s);
  result := bintoint(r);
  BinDump := BinDump+r+','+IntToStr(result)+','+IntToStr(offset)+','+D+CRLF;
  offset := offset + length(s);
end;
//-------------------------------------------------------
function TTemplate.valbin(I, N: integer; D:string): string;
var
  B, C: string;
begin
  B := StrRev(IntToBin(I));
  Result := B + StrRepeat('0', N-Length(B));
  BinDump := BinDump+Result+','+IntToStr(i)+','+IntToStr(offset)+','+D+CRLF;
  offset := offset + length(Result);
end;
//-------------------------------------------------------
procedure TTemplate.RAZ;
var i:integer;
begin
  IDProf1 := 0;
  IDProf2 := 0;
  Prof1 := '';
  Prof2 := '';
  NbAttr := 0;   
  SizeAttr := 0;
  SkillSize := 0;
  BinDump := '0111000000,-,0,Header'+CRLF;
  offset := 10;
  Attributes.Clear;
  AttributesValue.Clear;
  SkillsID.Clear;
  for i := 0 to 7 do SkillsID.Add(0);
  CodeOk := false;
end;                       
//-------------------------------------------------------
procedure TTemplate.MakeBuild;
var
  i, max, n: integer;
begin
  BinDump := '0111000000,-,0,Header'+CRLF;
  offset := 10;
  Bin:= '0111000000';
  Bin := Bin + valbin(IDProf1, 4, 'Prof 1');
  Bin := Bin + valbin(IDProf2, 4, 'Prof 2');
  Bin := Bin + valbin(Attributes.Count, 4, 'Attributes number');
  Bin := Bin + valbin(2, 4, 'Attributes size +4'); // valeur 4+2 -> 6
  for i := 0 to Attributes.Count - 1 do begin
    Bin := Bin + valbin(GetAttrIdByAbbr(Attributes[i]), 6, 'Attribute #'+Str(i+1));
    Bin := Bin + valbin(AttributesValue[i], 4, 'Attribute value');
  end;
  // choppe le max des id pour adapter la longueur
  max := 0;
  for i := 0 to 7 do begin
    if i<SkillsID.Count then
      if SKillsID[i]>max then max := SKillsID[i];
  end;
  n:= 0;
  while max<>0 do begin
    max := ToInt(max/2);
    n:= n+1;
  end;
  if n=0 then n:= 11;
  Bin := Bin + valbin(n-8, 4, 'Skills size +8');
  for i := 0 to 7 do begin
    if i<SkillsID.Count then
      Bin := Bin + valbin(SKillsID[i], n, 'Skill #'+Str(i+1))
    else
      Bin := Bin + valbin(0, n, 'Skill #'+Str(i+1));
  end;
  BinToCode;
end;
//-------------------------------------------------------
procedure TTemplate.Analyse;
var
  B, AttrName, SkillName: string;
  i, IDAttr, AttrValue, IDSkill: integer;
begin
  RAZ;
  CodeToBin;
  B := Bin;
  // Handle the new format (i.e leading '0111')
  if MidStr(B, 1, 4)='0111' then B := tronque(B, 4);
  // at least 22 bits
  if Length(B)<23 then Exit;
  // 6 first bits should be 000000
  if MidStr(B, 1, 6)<>'000000' then Exit;
  B := tronque(B, 6);
  // Primary prof
  IDProf1 := binval(MidStr(B, 1, 4), 'Prof 1');
  Prof1 := GetProfById(IDProf1);
  if Prof1='' then Exit;  
  // Secondary prof
  IDProf2 := binval(MidStr(B, 5, 4), 'Prof 2');
  Prof2 := GetProfById(IDProf2);
  if Prof2='' then Exit;
  // Attributes
  NbAttr := binval(MidStr(B, 9, 4), 'Attributes number');
  SizeAttr := 4 + binval(MidStr(B, 13, 4), 'Attributes size +4');
  B := tronque(B, 16);
  for i := 0 to NbAttr - 1 do begin
    if Length(B)<4+SizeAttr then Exit;
    // attribute name
    IDAttr := binval(MidStr(B, 1, SizeAttr), 'Attribute #'+Str(i+1));
    AttrName := GetAttrById(IDAttr);
    if AttrName='' then Exit;
    // attribute value       
    AttrValue := binval(MidStr(B, SizeAttr+1, 4), 'Attribute value');
    Attributes.Add(AttrName);
    AttributesValue.Add(AttrValue);
    B := tronque(B, 4+SizeAttr);
  end;
  // Skills    
  if Length(B)<4 then Exit;
  SkillSize := 8+binval(MidStr(B, 1, 4), 'Skill size +8');
  B := tronque(B, 4);
  for i := 0 to 7 do begin  
    if Length(B)<SkillSize then Exit;
    IDSkill := binval(MidStr(B, 1, SkillSize), 'Skill #'+Str(i+1));
    B := tronque(B, SkillSize);
    SkillsID[i] := IDSkill;
  end;
  Bin := B;
  CodeOK := True;
end;                                   
//-------------------------------------------------------
function TTemplate.BBCode: string;
var
  i: integer;
  Skill: IXMLDOMNode;
begin
  Result := 'Profession = [b][u]';
  if Prof1=Prof2 then
    Result := Result+Prof1
  else
    Result := Result+Prof1+'/'+Prof2;
  Result := Result+'[/u][/b]'+CRLF+CRLF;

  for i := 0 to Attributes.Count - 1 do begin
    Result := Result+GetAttributeNomByAbbr(Attributes[i])+' = '+IntToStr(AttributesValue[i])+CRLF;
  end;

  Result := Result+CRLF+'Code = [b]'+Code+'[/b]'+CRLF+CRLF;

  Result := Result+GetLangItem('skills')+' :'+CRLF;
  for i := 0 to SkillsID.Count-1 do begin
    Skill := GetSkillById(SkillsID[i]);
    Result := Result+' '+IntToStr(i+1)+'. ';
    if Skill=nil then
      Result := Result+CRLF
    else begin
      if Skill_Get_Elite(skill)=1 then
        Result := Result+'[b]'+Skill_Get_Name(Skill)+'[/b]'+CRLF
      else
        Result := Result+Skill_Get_Name(Skill)+CRLF;
    end;
  end;
end;
//-------------------------------------------------------
function TTemplate.GWBBCode: string;
var
  i: integer;
  Skill: IXMLDOMNode;
begin
  if Prof1=Prof2 then
    Result := '[build prof='+Prof1
  else
    Result := '[build prof='+Prof1+'/'+Prof2;
  for i := 0 to Attributes.Count - 1 do begin
    Result := Result+' '+Attributes[i]+'='+IntToStr(AttributesValue[i]);
  end;
  Result := Result+']';
  for i := 0 to SkillsID.Count-1 do begin
    Skill := GetSkillById(SkillsID[i]);
    if Skill=nil then
      Result := Result+'[Unknown skill id '+IntToStr(SkillsID[i])+']'
    else
      Result := Result+'['+Skill_Get_Name_Id(Skill)+']';
  end;
  Result := Result+'[/build]';
end;
//-------------------------------------------------------
function TTemplate.GetIndex(s:char):integer;
var
  i,n: integer;
begin
  Result := -1;
  n := length(Codes64);
  for I := 1 to n do
    if Codes64[i]=s then begin
      Result := i-1;
      break;
    end;
end;
//-------------------------------------------------------
procedure TTemplate.CodeToBin;
var
  i, n, v: integer;
  b : string;
begin
  Bin := '';
  n := length(Code);
  for I := 1 to n do begin
    v:= GetIndex(code[i]);
    if v < 0 then begin
      Bin := '';
      break;
    end;
    b := StrRev(IntToBin(v));
    Bin := Bin + b + StrRepeat('0', 6-Length(B));
  end;
end;
//-------------------------------------------------------
procedure TTemplate.BinToCode;
var
  n : integer;
  b, digit : string;
begin
  Code := '';
  if (length(Bin) mod 6)=0 then
    b := bin
  else
    b := Bin+StrRepeat('0', 6-(length(Bin) mod 6));
  while Length(B)>0 do begin
    digit := midStr(b, 1, 6);
    b := MidStr(b, 7, Length(B)-6);
    n := BinToInt(StrRev(digit));
    Code := Code+Codes64[n+1];
  end;
end;
//-------------------------------------------------------



end.
