import cv2

cap = cv2.VideoCapture(0, cv2.CAP_DSHOW)

if not cap.isOpened():
    print("Kameraet kunne ikke åbnes")
    exit()

while True:
    ret, frame = cap.read()
    if not ret:
        print("Kunne ikke læse frame")
        break

    cv2.imshow('Webcam', frame)

    # Tjek for tastetryk FØRST (denne er pålidelig)
    key = cv2.waitKey(1) & 0xFF
    if key == ord('q') or key == 27:  # 'q' eller ESC
        break

    # Sekundært tjek for X-knappen (kan fejle på nogle systemer)
    try:
        if cv2.getWindowProperty('Webcam', cv2.WND_PROP_VISIBLE) < 1:
            break
    except cv2.error:
        # Hvis getWindowProperty fejler, ignorerer vi det bare
        pass

cap.release()
cv2.destroyAllWindows()