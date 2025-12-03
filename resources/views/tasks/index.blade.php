<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo List</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <h1 class="text-3xl font-bold mb-8">Мой Todo List</h1>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Форма добавления задачи -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form action="{{ route('tasks.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <input
                    type="text"
                    name="title"
                    placeholder="Название задачи"
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                >
                @error('title')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                    <textarea
                        name="description"
                        placeholder="Описание (опционально)"
                        rows="3"
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    ></textarea>
            </div>
            <button
                type="submit"
                class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600"
            >
                Добавить задачу
            </button>
        </form>
    </div>

    <!-- Список задач -->
    <div class="space-y-3">
        @forelse($tasks as $task)
            <div class="bg-white rounded-lg shadow p-4 flex items-center justify-between">
                <div class="flex items-center flex-1">
                    <form action="{{ route('tasks.update', $task) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <input
                            type="checkbox"
                            name="completed"
                            onchange="this.form.submit()"
                            {{ $task->completed ? 'checked' : '' }}
                            class="w-5 h-5 text-blue-500 mr-3"
                        >
                    </form>
                    <div>
                        <h3 class="{{ $task->completed ? 'line-through text-gray-500' : 'font-semibold' }}">
                            {{ $task->title }}
                        </h3>
                        @if($task->description)
                            <p class="text-gray-600 text-sm">{{ $task->description }}</p>
                        @endif
                    </div>
                </div>
                <form action="{{ route('tasks.destroy', $task) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        class="text-red-500 hover:text-red-700"
                        onclick="return confirm('Удалить задачу?')"
                    >
                        Удалить
                    </button>
                </form>
            </div>
        @empty
            <p class="text-center text-gray-500 py-8">Задач пока нет. Добавьте первую!</p>
        @endforelse
    </div>
</div>
</body>
</html>
