import sys

tables = {
    "users": ["id (PK)", "firstname", "lastname", "purok", "username", "password", "email", "user_type"],
    "admins": ["id (PK)", "firstname", "lastname", "username", "email", "user_type", "specialization_id (FK)"],
    "categories": ["id (PK)", "name", "description"],
    "personnel": ["id (PK)", "name", "specialization_id (FK)", "star_rating"],
    "feedback": ["id (PK)", "user_id (FK)", "category_id (FK)", "status", "rating", "comment", "sentiment", "assigned_to (FK)"],
    "feedback_assignments": ["id (PK)", "feedback_id (FK)", "personnel_id (FK)", "status"],
    "surveys": ["id (PK)", "title", "status", "created_by (FK)", "assigned_to (FK)", "start_date", "end_date"],
    "survey_questions": ["id (PK)", "survey_id (FK)", "question_text", "question_type", "order_num"],
    "survey_responses": ["id (PK)", "survey_id (FK)", "user_id (FK)", "question_id (FK)", "answer_text"],
    "password_resets": ["id (PK)", "email", "token", "expires_at"],
    "login_attempts": ["id (PK)", "ip_address", "attempt_time", "is_success"],
    "settings": ["id (PK)", "name", "value"]
}

edge_links = [
    ("feedback", "user_id (FK)", "users", "id (PK)"),
    ("feedback", "category_id (FK)", "categories", "id (PK)"),
    ("feedback_assignments", "feedback_id (FK)", "feedback", "id (PK)"),
    ("feedback_assignments", "personnel_id (FK)", "personnel", "id (PK)"),
    ("personnel", "specialization_id (FK)", "categories", "id (PK)"),
    ("surveys", "created_by (FK)", "admins", "id (PK)"),
    ("survey_questions", "survey_id (FK)", "surveys", "id (PK)"),
    ("survey_responses", "survey_id (FK)", "surveys", "id (PK)"),
    ("survey_responses", "user_id (FK)", "users", "id (PK)"),
    ("survey_responses", "question_id (FK)", "survey_questions", "id (PK)")
]

xml = []
xml.append('<mxfile host="Electron" modified="2023-10-10T10:00:00.000Z" agent="Mozilla/5.0" version="21.1.2" type="device">')
xml.append('  <diagram id="erd_diagram" name="ERD">')
xml.append('    <mxGraphModel dx="1200" dy="800" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="1169" pageHeight="827" math="0" shadow="0">')
xml.append('      <root>')
xml.append('        <mxCell id="0" />')
xml.append('        <mxCell id="1" parent="0" />')

x_start = 40
y_start = 40
x_space = 280
y_space = 350
cols = 4

col_dict = {}

idx = 0
for table_name, cols_list in tables.items():
    col_x = idx % cols
    row_y = idx // cols
    
    pos_x = x_start + (col_x * x_space)
    pos_y = y_start + (row_y * y_space)
    height = 30 + (len(cols_list) * 30)
    
    xml.append(f'        <mxCell id="{table_name}" value="{table_name}" style="shape=table;startSize=30;container=1;collapsible=1;childLayout=tableLayout;fixedRows=1;rowLines=0;fontStyle=1;align=center;resizeLast=1;fillColor=#dae8fc;strokeColor=#6c8ebf;" vertex="1" parent="1">')
    xml.append(f'          <mxGeometry x="{pos_x}" y="{pos_y}" width="200" height="{height}" as="geometry" />')
    xml.append(f'        </mxCell>')
    
    for r_idx, col_name in enumerate(cols_list):
        row_id = f"{table_name}_r{r_idx}"
        col_type = ""
        if "PK" in col_name:
            col_type = "PK"
            col_name = col_name.replace(" (PK)", "")
        elif "FK" in col_name:
            col_type = "FK"
            col_name = col_name.replace(" (FK)", "")
            
        # Keep track of cell ID for edges
        cell_id = f"{table_name}_{col_name}"
        col_dict[(table_name, col_name)] = row_id # we connect to the row
            
        xml.append(f'        <mxCell id="{row_id}" value="" style="shape=tableRow;horizontal=0;startSize=0;swimlaneHead=0;swimlaneBody=0;top=0;left=0;bottom=0;right=0;dropTarget=0;collapsible=0;clipPath=1;fillColor=none;strokeColor=none;points=[[0,0.5],[1,0.5]];portConstraint=eastwest" vertex="1" parent="{table_name}">')
        xml.append(f'          <mxGeometry y="{30 + r_idx*30}" width="200" height="30" as="geometry" />')
        xml.append(f'        </mxCell>')
        
        xml.append(f'        <mxCell id="{row_id}_c1" value="{col_type}" style="shape=partialRectangle;html=1;whiteSpace=wrap;connectable=0;fillColor=none;top=0;left=0;bottom=0;right=0;overflow=hidden;pointerEvents=1;fontStyle=1;align=center;" vertex="1" parent="{row_id}">')
        xml.append(f'          <mxGeometry width="40" height="30" as="geometry" />')
        xml.append(f'        </mxCell>')
        
        xml.append(f'        <mxCell id="{row_id}_c2" value="{col_name}" style="shape=partialRectangle;html=1;whiteSpace=wrap;connectable=0;fillColor=none;top=0;left=0;bottom=0;right=0;align=left;spacingLeft=6;overflow=hidden;pointerEvents=1;" vertex="1" parent="{row_id}">')
        xml.append(f'          <mxGeometry x="40" width="160" height="30" as="geometry" />')
        xml.append(f'        </mxCell>')
        
    idx += 1

edge_idx = 0
for frm_tbl, frm_col, to_tbl, to_col in edge_links:
    frm_col = frm_col.split(" ")[0]
    to_col = to_col.split(" ")[0]
    frm_id = col_dict.get((frm_tbl, frm_col))
    to_id = col_dict.get((to_tbl, to_col))
    
    if frm_id and to_id:
        xml.append(f'        <mxCell id="edge_{edge_idx}" style="edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;jettySize=auto;html=1;exitX=0;exitY=0.5;exitDx=0;exitDy=0;entryX=1;entryY=0.5;entryDx=0;entryDy=0;" edge="1" parent="1" source="{frm_id}" target="{to_id}">')
        xml.append(f'          <mxGeometry relative="1" as="geometry" />')
        xml.append(f'        </mxCell>')
        edge_idx += 1

xml.append('      </root>')
xml.append('    </mxGraphModel>')
xml.append('  </diagram>')
xml.append('</mxfile>')

with open("c:/xampp/htdocs/brgy_rosario_feedback_system/docs/feedback_system_erd.drawio", "w") as f:
    f.write("\n".join(xml))

print("Created Draw.io file successfully.")
