.286
        name    melou.asm
Xmax    equ     640
Ymax    equ     480
;********************************************
afftxt  equ     13h                        ;*
dos     equ     21h                        ;*
bios    equ     10h                        ;*
keyb    equ     16h                        ;*
;********************************************
FG      equ     4BH
FD      equ     4DH
FB      equ     50H
ech     equ     01H

data    segment 'data'
nomj1   db 50,?,50 dup(' ')
nomj2   db 50,?,50 dup(' ')
titre   db 'JEU DE PUISSANCE 4  (REALISE PAR ARNAUD)'
crlf    db 10,13,'$'
asknom1 db 'Nom du joueur no1 : ','$'
asknom2 db 'Nom du joueur no2 : ','$'
merci   db 'Je vous remercie d avoir joue avec ce petit jeu, Arnaud.','$'
gagnant db 'Bravo a ','$'
quit    db 'Voulez-vous vraiment quitter ?','$'
ouinon  db 'OUI    NON','$'
coul1   db 9
coul2   db 12
coul3   db 3
grille  db 48 dup(0)

;********************************************
coordX  dw ?                               ;*
coordY  dw ?                               ;*
err1    dw ?                               ;*
err2    dw ?                               ;*
longX   dw ?                               ;*
longY   dw ?                               ;*
color   db ?                               ;*
pg      db ?                               ;*
fond    db ?                               ;*
;********************************************
data    ends
;********************************************

code    segment 'code'
        assume  cs:code,ds:data
debut:  mov     ax,data
        mov     ds,ax
;demande le nom des 2 joueurs
        call    erase
        call    saut_ligne
        mov     ah,09h
        mov     dx,offset asknom1
        int     dos
        mov     dx,offset nomj1
        mov     ah,0Ah
        int     dos
        call    saut_ligne
        call    saut_ligne
        mov     ah,09h
        mov     dx,offset asknom2
        int     dos
        mov     dx,offset nomj2
        mov     ah,0Ah
        int     dos
;mode video 640*480 16 couleurs
        mov     al,12h
        mov     ah,00h
        int     bios
        call    getpage
;trace cadres
        mov     al,coul3
        mov     color,al
        mov     bx,150
        mov     dx,447
        mov     ax,50
lpcad:  mov     cx,ax
        add     cx,6
        call    rectangle_p
        add     ax,3
        push    cx
        mov     cx,3
        call    circle_p
        pop     cx
        add     ax,47
        cmp     ax,480
        jb      lpcad
        mov     ax,53
        mov     dx,450
        mov     bx,444
        mov     cx,453
        call    rectangle_p
        mov     bx,447
        mov     cx,3
        call    circle_p
        mov     ax,453
        call    circle_p
;affichage titre
        mov     bh,pg
        mov     al,1
        mov     ah,13h
        mov     bl,0fEh
        mov     cx,40
        mov     bp,offset titre
        mov     dx,ds
        mov     es,dx
        mov     dh,0
        mov     dl,20
        int     bios
;affichage nom des joueurs et boules rouge et bleue
        mov     al,coul1
        mov     color,al
        mov     ax,490
        mov     bx,182
        mov     cx,10
        call    circle_p
        mov     bl,color
        mov     ax,seg nomj1
        mov     ds,ax
        mov     bp,offset nomj1
        mov     dh,11
        mov     dl,64

        call    write_car
        mov     al,coul2
        mov     color,al
        mov     ax,490
        mov     bx,246
        mov     cx,10
        call    circle_p
        mov     bl,color
        mov     ax,seg nomj2
        mov     ds,ax
        mov     bp,offset nomj2
        mov     dh,15
        mov     dl,64
        call    write_car
;initialisation
        mov     al,1   ;x position
        mov     ah,1   ;no joueur
        xor     bl,bl  ;y position
;jeu
lp:     call    boule_joueur
;saisie caractere au clavier
        call    keyboard
        cmp     bh,FG
        jne     F2
        cmp     al,1
        jne     F22
        call    boule_noire
        mov     al,8
        jmp     lp
F22:    call    boule_noire
        dec     al
        jmp     lp
F2:     cmp     bh,FD
        jne     F3
        cmp     al,8
        jne     F33
        call    boule_noire
        mov     al,1
        jmp     lp
F33:    call    boule_noire
        inc     al
        jmp     lp
F3:     cmp     bh,ech
        je      quitter
        jmp     lp


quitter: mov    bp,offset quit
        mov     dh,3
        mov     dl,5
        mov     bl,15
        call    write_car
        mov     bp,offset ouinon
        mov     dl,40
        mov     bl,12
        call    write_car
        call    pause


;retour mode texte dos
finjeu: mov     ah,00h
        mov     al,3h
        int     bios
;affichage remerciements
        mov     ah,09h
        mov     dx,offset merci
        int     dos
        mov     ah,09h
        mov     dx,offset crlf
        int     dos
;retour dos
        mov     ah,4ch
        int     dos

KEYBOARD PROC
        push    ax
        mov     ah,00h
        int     keyb
        mov     bh,ah
        pop     ax
        ret
KEYBOARD ENDP

BOULE_NOIRE PROC
        push    ax
        push    bx
        push    cx
        push    dx
        mov     color,0
        xor     ah,ah
        mov     dh,50
        mul     dh
        add     ax,28
        mov     cx,20
        xor     bh,bh
        xchg    ax,bx
        mul     dh
        add     ax,119
        xchg    ax,bx
        call    circle_p
        pop     dx
        pop     cx
        pop     bx
        pop     ax
        ret
BOULE_NOIRE ENDP

BOULE_JOUEUR PROC
        push    ax
        push    bx
        push    cx
        push    dx
        push    ax
        mov     ax,seg coul1
        mov     ds,ax
        pop     ax
        mov     bp,offset coul1
        cmp     ah,2
        jne     suibn
        inc     bp
suibn:  mov     bh,DS:[bp]
        mov     color,bh
        xor     ah,ah
        mov     dh,50
        mul     dh
        add     ax,28
        mov     cx,20
        xor     bh,bh
        xchg    ax,bx
        mul     dh
        add     ax,119
        xchg    ax,bx
        call    circle_p
        pop     dx
        pop     cx
        pop     bx
        pop     ax
        ret
BOULE_JOUEUR ENDP

;********************************************
WRITE_CAR PROC
;BP=offset texte
;DH=ligne DL=colonne
;BL=couleur
        push    ax
        push    bx
        push    cx
        push    dx
        push    bp
        push    es
        mov     ax,ds
        mov     es,ax
        inc     bp
        xor     ch,ch
        mov     cl,DS:[BP]
        inc     bp
        inc     cl
lpwri:  dec     cl
        mov     al,cl
        add     al,dl
        dec     al
        cmp     al,80
        jnb     lpwri
        mov     bh,pg
        mov     al,1
        mov     ah,13h
        add     bl,0f0h
        int     bios
        pop     es
        pop     bp
        pop     dx
        pop     cx
        pop     bx
        pop     ax
        ret
WRITE_CAR ENDP

ERASE   PROC
        push    ax
        mov     ah,0fh
        int     10h
        mov     ah,00h
        int     10h
        pop     ax
        ret
ERASE   ENDP

SAUT_LIGNE PROC
        mov     ah,09h
        mov     dx,offset crlf
        int     dos
        ret
SAUT_LIGNE ENDP

PAUSE   PROC
;attend qu'on appuie sur une touche pour continuer
        push    ax
        mov     ah,8
        int     dos
        pop     ax
        ret        
PAUSE   ENDP

GETPAGE PROC
;recupere la page video pour les fonctions bios video
        push    ax
        push    bx
        mov     ah,0fh
        int     bios
        mov     pg,bh
        pop     bx
        pop     ax
        ret
GETPAGE ENDP

abs_SI PROC
;effectue la valeur absolue de SI
        cmp     SI,0
        jnle    fabsSI
        neg     SI
fabsSI: ret
abs_SI ENDP

abs_DI PROC
;effectue la valeur absolue de DI
        cmp     DI,0
        jnle    fabsDI
        neg     DI
fabsDI: ret
abs_DI ENDP

PRINTPIXEL PROC
;imprime le pixel a l'ecran en faisant avant une translation
        push    ax
        push    bx
        push    cx
        push    dx
        mov     cx,ax
        mov     dx,bx
        add     ax,coordX
        add     bx,coordY
        cmp     ax,xmax
        jnbe    print2
        cmp     bx,ymax
        jnbe    print2
        call    affpixel
print2: sub     ax,cx
        sub     ax,cx
        cmp     ax,xmax
        jnbe    print3
        cmp     bx,ymax
        jnbe    print3
        call    affpixel
print3: sub     bx,dx
        sub     bx,dx
        cmp     ax,xmax
        jnbe    print4
        cmp     bx,ymax
        jnbe    print4
        call    affpixel
print4: add     ax,cx
        add     ax,cx
        cmp     ax,xmax
        jnbe    print5
        cmp     bx,ymax
        jnbe    print5
        call    affpixel
print5: pop     dx
        pop     cx
        pop     bx
        pop     ax
        ret
PRINTPIXEL ENDP

CIRCLE PROC
;trace un cercle de centre (AX,BX) et de rayon CX de couleur 'color'
        push    ax
        push    bx
        push    cx
        push    si
        push    di
        push    bp
        mov     coordX,ax
        mov     coordY,bx
        mov     ax,cx
        xor     bx,bx
        call    printpixel
        xchg    ax,bx
        call    printpixel
        xchg    ax,bx
        cmp     cx,0
        je      fcirq1
        xor     cx,cx
deb1q1: cmp     ax,bx
        jb      deb2q1
        xor     bp,bp
        mov     di,ax
        shl     di,1
        sub     bp,di
        inc     bp
        mov     SI,bx
        shl     SI,1
        add     SI,bp
        inc     si
        mov     di,bx
        shl     di,1
        inc     di
        add     SI,cx
        add     di,cx
        mov     err1,SI
        mov     err2,di
        call    abs_SI
        call    abs_DI
        cmp     SI,di
        jnbe    inf1q1
        dec     ax
        inc     bx
        mov     cx,err1
        call    printpixel
        xchg    ax,bx
        call    printpixel
        xchg    ax,bx
        jmp     deb1q1
inf1q1: inc     bx
        mov     cx,err2
        call    printpixel
        xchg    ax,bx
        call    printpixel
        xchg    ax,bx
        jmp     deb1q1
deb2q1: xor     bp,bp ;sert a rien
fcirq1: pop     bp
        pop     di
        pop     SI
        pop     cx
        pop     bx
        pop     ax
        ret                
CIRCLE ENDP

CIRCLE2 PROC
;trace un cercle de centre (AX,BX) et de rayon CX de couleur 'color'
        push    ax
        push    bx
        push    cx
        push    si
        push    di
        push    bp
        mov     coordX,ax
        mov     coordY,bx
        mov     ax,cx
        xor     bx,bx
        call    printpixel
        xchg    ax,bx
        call    printpixel
        xchg    ax,bx
        cmp     cx,0
        je      fcirq3
        xor     cx,cx
deb1q3: cmp     ax,bx
        jb      deb2q3
        xor     bp,bp
        mov     di,ax
        shl     di,1
        sub     bp,di
        inc     bp
        mov     SI,bx
        shl     SI,1
        add     SI,bp
        inc     si
        mov     di,bx
        shl     di,1
        inc     di
        add     SI,cx
        add     di,cx
        mov     err1,SI
        mov     err2,di
        call    abs_SI
        call    abs_DI
        cmp     SI,di
        jnbe    inf1q3
        inc     bx
        mov     cx,err1
        call    printpixel
        xchg    ax,bx
        call    printpixel
        dec     bx
        call    printpixel
        xchg    ax,bx
        call    printpixel
        jmp     deb1q3
inf1q3: inc     bx
        mov     cx,err2
        call    printpixel
        xchg    ax,bx
        call    printpixel
        xchg    ax,bx
        jmp     deb1q3
deb2q3: xor     bp,bp ;sert a rien
fcirq3: pop     bp
        pop     di
        pop     SI
        pop     cx
        pop     bx
        pop     ax
        ret                
CIRCLE2 ENDP

CIRCLE2_P PROC
;trace un cercle de centre (AX,BX) et de rayon CX de couleur 'color'
        push    ax
        push    bx
        push    cx
        push    si
        push    di
        push    bp
        mov     coordX,ax
        mov     coordY,bx
        mov     ax,cx
        xor     bx,bx
        call    circle_lig
        xchg    ax,bx
        call    circle_lig
        xchg    ax,bx
        cmp     cx,0
        je      fcirq4
        xor     cx,cx
deb1q4: cmp     ax,bx
        jb      deb2q4
        xor     bp,bp
        mov     di,ax
        shl     di,1
        sub     bp,di
        inc     bp
        mov     SI,bx
        shl     SI,1
        add     SI,bp
        inc     si
        mov     di,bx
        shl     di,1
        inc     di
        add     SI,cx
        add     di,cx
        mov     err1,SI
        mov     err2,di
        call    abs_SI
        call    abs_DI
        cmp     SI,di
        jnbe    inf1q4
        inc     bx
        mov     cx,err1
        call    printpixel
        xchg    ax,bx
        call    printpixel
        dec     bx
        call    circle_lig
        xchg    ax,bx
        call    circle_lig
        jmp     deb1q4
inf1q4: inc     bx
        mov     cx,err2
        call    circle_lig
        xchg    ax,bx
        call    circle_lig
        xchg    ax,bx
        jmp     deb1q4
deb2q4: xor     bp,bp ;sert a rien
fcirq4: pop     bp
        pop     di
        pop     SI
        pop     cx
        pop     bx
        pop     ax
        ret                
CIRCLE2_P ENDP

CIRCLE_LIG PROC
        push    ax
lpcirlig: call    printpixel
        dec     ax
        jnl     lpcirlig
        pop     ax
        ret
CIRCLE_LIG ENDP

CIRCLE_P PROC
;trace un disque de centre (AX,BX) et de rayon CX de couleur 'color'
        push    ax
        push    bx
        push    cx
        push    si
        push    di
        push    bp
        mov     coordX,ax
        mov     coordY,bx
        mov     ax,cx
        xor     bx,bx
        call    circle_lig
        xchg    ax,bx
        call    circle_lig
        xchg    ax,bx
        cmp     cx,0
        je      fcirq2
        xor     cx,cx
deb1q2: cmp     ax,bx
        jb      deb2q2
        xor     bp,bp
        mov     di,ax
        shl     di,1
        sub     bp,di
        inc     bp
        mov     SI,bx
        shl     SI,1
        add     SI,bp
        inc     si
        mov     di,bx
        shl     di,1
        inc     di
        add     SI,cx
        add     di,cx
        mov     err1,SI
        mov     err2,di
        call    abs_SI
        call    abs_DI
        cmp     SI,di
        jnbe    inf1q2
        dec     ax
        inc     bx
        mov     cx,err1
        call    circle_lig
        xchg    ax,bx
        call    circle_lig
        xchg    ax,bx
        jmp     deb1q2
inf1q2: inc     bx
        mov     cx,err2
        call    circle_lig
        xchg    ax,bx
        call    circle_lig
        xchg    ax,bx
        jmp     deb1q2
deb2q2: xor     bp,bp ;sert a rien
fcirq2: pop     bp
        pop     di
        pop     SI
        pop     cx
        pop     bx
        pop     ax
        ret                
CIRCLE_P ENDP

RECTANGLE PROC
;trace le contour d'un rectangle
;(AX,BX) & (CX,DX) = coordonnees de 2 points extremes du triangle
        call    ligneh
        push    bx
        mov     bx,dx
        call    ligneh
        pop     bx
        push    cx
        push    dx
        xchg    cx,dx
        call    lignev
        push    ax
        mov     ax,dx
        call    lignev
        pop     ax
        pop     dx
        pop     cx
        ret
RECTANGLE ENDP

RECTANGLE_P PROC
;trace un rectangle plein
;(AX,BX) & (CX,DX) = coordonnees de 2 points extremes du triangle
        push    ax
        push    bx
        push    cx
        push    dx
        push    di
        cmp     ax,cx
        jb      rect1
        xchg    ax,cx
rect1:  cmp     bx,dx
        jb      rect2
        xchg    bx,dx
rect2:  mov     di,dx
        mov     dx,bx
rect3:  call    ligneh
        inc     bx
        inc     dx
        cmp     bx,di
        jbe    rect3
        pop     di
        pop     dx
        pop     cx
        pop     bx
        pop     ax
        ret
RECTANGLE_P ENDP

LIGNEH  PROC
;trace une ligne horizontale
;(AX,BX) = point de depart
;CX = abscisse du point d'arrivee
        push    ax
        push    cx
        cmp     ax,cx
        jb      lpligh
        xchg    ax,cx
lpligh: call    affpixel
        inc     ax
        cmp     ax,cx
        jne     lpligh
        call    affpixel
        pop     cx
        pop     ax
        ret
LIGNEH  ENDP

LIGNEV  PROC
;trace une ligne verticale
;(AX,BX) = point de depart
;CX = ordonnee du point d'arrivee
        push    bx
        push    cx
        cmp     bx,cx
        jb      lpligv
        xchg    bx,cx
lpligv: call    affpixel
        inc     bx
        cmp     bx,cx
        jne     lpligv
        call    affpixel
        pop     cx
        pop     bx
        ret
LIGNEV  ENDP

LIGNE   PROC
;trace une ligne
;(AX,BX) = point de depart
;(CX,DX) = point d'arrivee
        pusha
        cmp     ax,cx
        jb      lig0
        xchg    ax,cx
        xchg    bx,dx
lig0:   cmp     bx,dx
        jnbe    lig00
        mov     si,cx
        sub     si,ax
        mov     di,dx
        sub     di,bx
        cmp     si,di
        jb      lig2

lig1:   mov     bp,di
        shl     bp,1
        sub     bp,si
        shl     si,1
        shl     di,1
lplg1:  cmp     ax,cx
        jnbe    finlg
        call    affpixel
        cmp     bp,0
        jl      lgs1
        sub     bp,si
        inc     bx
lgs1:   inc     ax
        add     bp,di
        jmp     lplg1

lig2:   xchg    ax,bx
        xchg    cx,dx
        xchg    si,di
        mov     bp,di
        shl     bp,1
        sub     bp,si
        shl     si,1
        shl     di,1
lplg2:  cmp     ax,cx
        jnbe    finlg
        xchg    ax,bx
        call    affpixel
        xchg    ax,bx
        cmp     bp,0
        jl      lgs2
        sub     bp,si
        inc     bx
lgs2:   inc     ax
        add     bp,di
        jmp     lplg2

lig00:  mov     si,cx
        sub     si,ax
        mov     di,bx
        sub     di,dx
        cmp     si,di
        jb      lig4

lig3:   mov     bp,di
        shl     bp,1
        sub     bp,si
        shl     si,1
        shl     di,1
lplg3:  cmp     ax,cx
        jnbe    finlg
        call    affpixel
        cmp     bp,0
        jl      lgs3
        sub     bp,si
        dec     bx
lgs3:   inc     ax
        add     bp,di
        jmp     lplg3

lig4:   xchg    ax,bx
        xchg    cx,dx
        xchg    ax,cx
        xchg    bx,dx
        xchg    si,di
        mov     bp,di
        shl     bp,1
        sub     bp,si
        shl     si,1
        shl     di,1
lplg4:  cmp     ax,cx
        jnbe    finlg
        xchg    ax,bx
        call    affpixel
        xchg    ax,bx
        cmp     bp,0
        jl      lgs4
        sub     bp,si
        dec     bx
lgs4:   inc     ax
        add     bp,di
        jmp     lplg4
finlg:  popa
        ret
LIGNE   ENDP

AFFPIXEL PROC
; imprime un pixel de couleur 'color'
; aux coordonnees (AX,BX)
        push    cx
        push    ax
        push    bx
        push    dx
        mov     cx,ax
        mov     dx,bx
        mov     al,color
        mov     bh,pg
        mov     ah,0Ch
        int     bios
        pop     dx
        pop     bx
        pop     ax
        pop     cx
        ret
AFFPIXEL ENDP

COLORIAGE PROC
;defini la couleur de fond a colorier et
;colorie la surface a partir du point (AX,BX)
;de la couleur color
        push    ax
        push    bx
        push    cx
        call    setfond
        call    lggerm
        pop     cx
        pop     bx
        pop     ax
        ret
COLORIAGE ENDP

LGGERM  PROC
;colorie une surface a partir du point (AX,BX)
;de la couleur color
        call    GETCOLOR
        cmp     cl,fond
        jne     dep2
        call    affpixel
        push    ax
;va juqu'au bout a drte
lpdrte: inc     ax
        cmp     ax,xmax
        jnb     findrt
        call    GETCOLOR
        cmp     cl,fond
        jne     findrt
        call    affpixel
        jmp     lpdrte
findrt: mov     dx,ax
        dec     dx
        pop     ax
;dx contient la position drte
lpgche: dec     ax
        cmp     ax,xmax
        jnb     fingch
        call    GETCOLOR
        cmp     cl,fond
        jne     fingch
        call    affpixel
        jmp     lpgche
fingch: inc     ax
;si contient la position gche
;relance l'algorithme pour la ligne dessus et la ligne dessous
        inc     bx
        cmp     bx,ymax
        jnb     bottom
        call    HORIZ
bottom: sub     bx,2
        cmp     bx,ymax
        jnb     dep3
        call    HORIZ
dep3:   inc     bx
dep2:   ret
LGGERM  ENDP

HORIZ   PROC
;sous-programme de LGGERM
        push    ax
        dec     ax
hor1:   inc     ax
        cmp     ax,dx
        jnbe    endhor1
        push    ax
        push    dx
        call    LGGERM
        pop     dx
        pop     ax
        jmp     hor1
endhor1: pop     ax
        ret
HORIZ   ENDP

SETFOND PROC
;recupere la couleur au pixel de depart comme couleur de fond
        call    getcolor
        mov     fond,cl
;enregistre dans la variable 'fond'
        ret
SETFOND ENDP

GETCOLOR PROC
;recupere la couleur aux coordonnees (AX,BX) et la met dans CX
        push    bx
        push    dx
        push    ax
        mov     cx,ax
        mov     dx,bx
        mov     bh,pg
        mov     ah,0Dh
        int     bios
        xor     cx,cx
        mov     cl,al
        pop     ax
        pop     dx
        pop     bx
        ret
GETCOLOR ENDP

;********************************************

code    ends

stack   segment stack
        dw 1000 dup(?)
stack   ends

        end     debut
