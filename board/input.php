<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>새 게시물 작성</title>
    <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="cumtom.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center mb-4">새 게시물 작성</h1>
    <form method="POST" action="input_process.php" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="title" class="form-label">제목</label>
            <input type="text" id="title" name="title" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="content" class="form-label">내용</label>
            <div id="editor">
            </div>
            <textarea id="content" name="content" class="form-control" style="display:none;"></textarea>
        </div>

        <div class="mb-3">
            <label for="username" class="form-label">사용자 이름</label>
            <input type="text" id="username" name="username" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="userpassword" class="form-label">비밀번호</label>
            <input type="password" id="userpassword" name="userpassword" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="attachment" class="form-label">파일 첨부</label>
            <input type="file" id="attachment" name="attachment" class="form-control">
            <small class="text-danger d-none" id="fileError">한글이나 공백이 포함된 파일은 업로드할 수 없습니다.</small>
        </div>

        <button type="submit" class="btn btn-success">작성하기</button>
    </form>
</div>

<script type="importmap">
    {
        "imports": {
            "ckeditor5": "https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.js",
            "ckeditor5/": "https://cdn.ckeditor.com/ckeditor5/43.3.1/"
        }
    }
</script>
<script type="module">
    import {
        ClassicEditor,
        Essentials,
        Paragraph,
        Bold,
        Italic,
        Font
    } from 'ckeditor5';
    ClassicEditor
        .create( document.querySelector( '#editor' ), {
            plugins: [ Essentials, Paragraph, Bold, Italic, Font ],
            toolbar: [
                'undo', 'redo', '|', 'bold', 'italic', '|',
                'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor'
            ],
            fontFamily: {
                options: [
                    'default',
                    'Pretendard, sans-serif',
                    'Arial, Helvetica, sans-serif',
                    'Courier New, Courier, monospace',
                    'Georgia, serif',
                    'Tahoma, Geneva, sans-serif',
                    'Times New Roman, Times, serif',
                    'Verdana, Geneva, sans-serif'
                ],
                supportAllValues: true // 모든 사용자 정의 값을 허용
            }
        } )
        .then( editor => {
            window.editor = editor;

            // 폼 전송 시 에디터 데이터를 textarea에 복사
            document.querySelector('form').addEventListener('submit', (event) => {
                document.querySelector('#content').value = editor.getData();
            });
        } )
        .catch( error => {
            console.error( error );
        } );
</script>
<script>
    document.getElementById('attachment').addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
            const fileName = file.name;
            const invalidCharacters = /[\u3131-\uD79D\s]/; // 한글 및 공백 정규식
            if (invalidCharacters.test(fileName)) {
                document.getElementById('fileError').classList.remove('d-none');
                this.value = ''; // 파일 입력 초기화
            } else {
                document.getElementById('fileError').classList.add('d-none');
            }
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>