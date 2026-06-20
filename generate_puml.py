import urllib.request
import json
import ssl

puml_source = '''@startuml
skinparam maxMessageSize 150
skinparam monochrome false
skinparam backgroundColor white
skinparam sequence {
    ArrowColor black
    LifeLineBorderColor #00BFFF
    LifeLineBackgroundColor #87CEEB
    ParticipantBorderColor black
    ParticipantBackgroundColor #87CEEB
    ParticipantFontName Arial
    ParticipantFontSize 12
    ActorBorderColor black
    ActorBackgroundColor white
    ActorFontName Arial
    ActorFontSize 12
}

actor "Warga" as Warga
participant "Cloud SSO\nDosen" as SSO
participant "Peminjaman-Service" as PS
participant "Database\nLokal" as DB
participant "Legacy Audit\nSOAP Dosen" as SOAP
participant "RabbitMQ\nDosen" as MQ

Warga -> SSO : 1: Login dan meminta JWT
activate SSO
SSO --> Warga : 1.1: Meminta JWT berisi identitas user
deactivate SSO

Warga -> PS : 2: POST /api/v1/secure/loans + Bearer JWT
activate PS

PS -> PS : 2.1: Decode payload JWT

PS -> DB : 2.2: Simpan mapping role lokal
activate DB
DB --> PS : 2.2.1: OK
deactivate DB

PS -> PS : 2.3: Validasi role member

PS -> DB : 2.4: Simpan data peminjaman\n(Status Active)
activate DB
DB --> PS : 2.4.1: Data Peminjaman berhasil dibuat
deactivate DB

PS -> SOAP : 3: Kirim SOAP AuditRequest\nTeamID: TEAM-05\nActivityName: CREATE_LOAN\nLogContent: data peminjaman
activate SOAP
SOAP --> PS : 3.1: Mengembalikan ReceiptNumber
deactivate SOAP

PS -> DB : 4: Update audit_receipt pada loans
activate DB
DB --> PS : 4.1: OK
deactivate DB

PS -> MQ : 5: Publish event loan.created\n(routing key: peminjaman.loan.created)
activate MQ
MQ --> PS : 5.1: OK
deactivate MQ

PS --> Warga : 6: Response 201: Peminjaman berhasil
deactivate PS
@enduml'''

data = json.dumps({"diagram_source": puml_source}).encode('utf-8')
ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

req = urllib.request.Request("https://kroki.io/plantuml/png", data=data, headers={'Content-Type': 'application/json'})
try:
    with urllib.request.urlopen(req, context=ctx) as response:
        with open("sequence_diagram_vp.png", "wb") as f:
            f.write(response.read())
    print("SUCCESS")
except Exception as e:
    print("ERROR:", e)
