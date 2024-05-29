import face_recognition
import json

def process_image(image_path):
    image = face_recognition.load_image_file(image_path)
    face_encodings = face_recognition.face_encodings(image)
    if face_encodings:
        # Convertendo o vetor de codificação para uma lista para serialização JSON
        encoding_list = face_encodings[0].tolist()
        print(json.dumps(encoding_list))
    else:
        print("No faces detected")

if __name__ == "__main__":
    process_image("path_to_your_image.jpg")
